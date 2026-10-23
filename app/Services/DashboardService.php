<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Container;
use App\Models\Device;
use App\Models\InventoryItem;
use App\Models\Reading;
use App\Models\User;
use App\Support\Fmt;
use App\Support\Setting;

class DashboardService
{
    public static function readingRow(Reading $r): array
    {
        return [
            'id' => $r->id,
            'device' => $r->device?->device_id,
            'ph' => $r->ph_value === null ? '—' : number_format($r->ph_value, 2),
            'status' => $r->status_label,
            'class' => $r->status_class,
            'at' => Fmt::dt($r->measured_at),
        ];
    }

    public static function stats(User $user): array
    {
        $canMonitor = $user->can('monitoring.view');
        $out = [
            'can_monitor' => $canMonitor,
            'active_alerts' => Alert::open()->visibleTo($user)->count(),
            'total_items' => InventoryItem::where('status', 'active')->count(),
            'low_stock' => InventoryItem::where('status', 'active')->where('quantity', '>', 0)->whereColumn('quantity', '<=', 'min_stock')->count(),
            'out_of_stock' => InventoryItem::where('status', 'active')->where('quantity', '<=', 0)->count(),
            'needs_inspection' => Container::where('status', '!=', 'retired')->whereNotNull('next_inspection_date')->whereDate('next_inspection_date', '<=', today())->count(),
            'due_replacement' => Container::where('status', '!=', 'retired')->whereNotNull('replacement_due_date')->whereDate('replacement_due_date', '<=', today())->count(),
        ];
        if (!$canMonitor) return $out;

        $latest = Reading::with('device')->latest('measured_at')->first();
        $todayStart = Fmt::dayStart(now(Fmt::tz())->toDateString());
        $statusMix = Reading::where('measured_at', '>=', $todayStart)
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $today = Reading::where('measured_at', '>=', $todayStart);
        $todayTotal = (clone $today)->count();
        $todayOk = (clone $today)->where('status', 'normal')->count();

        $devices = Device::where('is_active', true)->get();
        $lastSeen = Device::max('last_seen_at');

        $chart = Reading::whereIn('status', ['low', 'normal', 'high'])->latest('measured_at')->limit(60)->get()->reverse()->values();

        return $out + [
            'latest' => $latest ? self::readingRow($latest) : null,
            'today_count' => $todayTotal,
            'in_range_pct' => $todayTotal ? (int) round($todayOk / $todayTotal * 100) : null,
            'device_online' => $devices->contains(fn ($d) => $d->isOnline()),
            'device_count' => $devices->count(),
            'last_upload' => $lastSeen ? Fmt::dt($lastSeen) : null,
            'ph_min' => (float) Setting::get('ph_min'),
            'ph_max' => (float) Setting::get('ph_max'),
            'chart' => [
                'labels' => $chart->map(fn ($r) => Fmt::dt($r->measured_at, 'M d H:i'))->all(),
                'values' => $chart->map(fn ($r) => $r->ph_value)->all(),
            ],
            'status_mix' => [
                'labels' => ['In range', 'Low pH', 'High pH', 'Other'],
                'values' => [(int) ($statusMix['normal'] ?? 0), (int) ($statusMix['low'] ?? 0), (int) ($statusMix['high'] ?? 0), (int) (($statusMix['invalid'] ?? 0) + ($statusMix['sensor_error'] ?? 0))],
            ],
            'recent' => Reading::with('device')->latest('measured_at')->limit(8)->get()->map(fn ($r) => self::readingRow($r))->all(),
        ];
    }
}
