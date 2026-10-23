<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Container;
use App\Models\InventoryItem;
use App\Models\User;
use App\Models\UserNotification;
use App\Support\Setting;

class AlertService
{
    /** Creates an alert unless an identical one is still open (dedupe) - then it only counts the repeat. */
    public static function raise(string $type, string $severity, string $title, ?string $description, string $key, array $ctx = []): Alert
    {
        $open = Alert::where('dedupe_key', $key)->open()->first();
        if ($open) {
            $open->update(['occurrences' => $open->occurrences + 1, 'last_seen_at' => now(), 'description' => $description ?? $open->description]);
            return $open;
        }
        $alert = Alert::create(array_merge([
            'type' => $type, 'severity' => $severity, 'title' => $title, 'description' => $description,
            'dedupe_key' => $key, 'last_seen_at' => now(),
        ], $ctx));
        self::notify($alert);
        return $alert;
    }

    /** Recovery logic: closes open alerts whose condition has cleared. */
    public static function recover(array|string $keys, string $note = 'Auto-resolved: the condition returned to normal.'): void
    {
        Alert::whereIn('dedupe_key', (array) $keys)->open()
            ->update(['status' => 'resolved', 'resolved_at' => now(), 'resolution_notes' => $note]);
    }

    private static function notify(Alert $alert): void
    {
        $pref = match (true) {
            str_starts_with($alert->type, 'ph_'), str_starts_with($alert->type, 'sensor_') => 'notify_ph_alerts',
            in_array($alert->type, ['low_stock', 'out_of_stock']) => 'notify_inventory_alerts',
            default => 'notify_container_alerts',
        };
        if (!Setting::get($pref)) return;

        $roles = config("aquatrack.alert_roles.{$alert->type}", ['owner']);
        foreach (User::where('is_active', true)->whereIn('role', $roles)->get() as $user) {
            UserNotification::create([
                'user_id' => $user->id, 'alert_id' => $alert->id, 'title' => $alert->title,
                'body' => $alert->description, 'url' => route('alerts.index', ['status' => 'new']),
            ]);
        }
    }

    public static function syncItem(InventoryItem $item): void
    {
        $low = "stock:{$item->id}:low";
        $out = "stock:{$item->id}:out";
        if ($item->status !== 'active') {
            self::recover([$low, $out], 'Item archived.');
            return;
        }
        $ctx = ['related_type' => 'item', 'related_id' => $item->id, 'related_label' => $item->name];
        if ($item->quantity <= 0) {
            self::recover($low, 'Escalated to out of stock.');
            self::raise('out_of_stock', 'critical', "Out of stock: {$item->name}", "{$item->name} ({$item->sku}) has no stock left.", $out, $ctx);
        } elseif ($item->quantity <= $item->min_stock) {
            self::recover($out, 'Restocked above zero.');
            self::raise('low_stock', 'warning', "Low stock: {$item->name}",
                "Only ".\App\Support\Fmt::qty($item->quantity)." {$item->unit} left; the minimum is ".\App\Support\Fmt::qty($item->min_stock).'.', $low, $ctx);
        } else {
            self::recover([$low, $out], 'Stock is back above the minimum.');
        }
    }

    public static function syncContainer(Container $c): void
    {
        $insp = "insp:{$c->id}";
        $repl = "repl:{$c->id}";
        $ctx = ['related_type' => 'container', 'related_id' => $c->id, 'related_label' => $c->code];
        if ($c->status === 'retired') {
            self::recover([$insp, $repl], 'Container retired.');
            return;
        }
        if ($c->is_inspection_overdue) {
            self::raise('inspection_overdue', 'warning', "Inspection overdue: {$c->code}",
                "Inspection was due on ".$c->next_inspection_date->format('M d, Y').'.', $insp, $ctx);
        } else {
            self::recover($insp, 'Inspection recorded or rescheduled.');
        }
        if ($c->is_replacement_due) {
            self::raise('replacement_due', 'warning', "Replacement due: {$c->code}",
                "Replacement was due on ".$c->replacement_due_date->format('M d, Y').'.', $repl, $ctx);
        } else {
            self::recover($repl, 'Replacement handled or rescheduled.');
        }
    }
}
