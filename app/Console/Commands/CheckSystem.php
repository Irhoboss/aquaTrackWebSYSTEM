<?php

namespace App\Console\Commands;

use App\Models\Container;
use App\Models\Device;
use App\Models\InventoryItem;
use App\Services\AlertService;
use App\Support\Fmt;
use App\Support\Setting;
use Illuminate\Console\Command;

class CheckSystem extends Command
{
    protected $signature = 'aquatrack:check';
    protected $description = 'Raise/resolve alerts for offline devices, stock levels and container inspections.';

    public function handle(): int
    {
        foreach (Device::where('is_active', true)->get() as $d) {
            $key = "offline:{$d->id}";
            $grace = $d->created_at->gt(now()->subMinutes((int) Setting::get('offline_minutes')));
            if (!$d->isOnline() && !$grace) {
                AlertService::raise('sensor_offline', 'critical', "Sensor not reporting: {$d->name}",
                    $d->last_seen_at ? 'Last upload was '.Fmt::dt($d->last_seen_at).'.' : 'This device has never uploaded a reading.',
                    $key, ['device_id' => $d->id, 'related_type' => 'device', 'related_id' => $d->id, 'related_label' => $d->name]);
            } elseif ($d->isOnline()) {
                AlertService::recover($key, 'Auto-resolved: the device is reporting again.');
            }
        }

        InventoryItem::where('status', 'active')->each(fn ($i) => AlertService::syncItem($i));

        Container::where('status', '!=', 'retired')->each(function (Container $c) {
            if ($c->is_inspection_overdue && in_array($c->status, ['available', 'in_use'])) {
                $c->update(['status' => 'needs_inspection']);
            }
            AlertService::syncContainer($c->fresh());
        });

        $this->info('AquaTrack check complete.');
        return self::SUCCESS;
    }
}
