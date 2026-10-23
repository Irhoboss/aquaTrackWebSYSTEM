<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Container;
use App\Models\ContainerInspection;
use App\Models\ContainerMaintenanceRecord;
use App\Models\Device;
use App\Models\InventoryItem;
use App\Models\Reading;
use App\Models\StockMovement;
use App\Support\Fmt;
use App\Support\Setting;
use Carbon\Carbon;

class ReportService
{
    public const TYPES = [
        'ph_summary' => 'pH readings (day / week / month)',
        'ph_out_of_range' => 'pH readings outside thresholds',
        'sensor_availability' => 'Sensor availability and missing periods',
        'inventory_balances' => 'Inventory balances',
        'stock_movements' => 'Stock-in and stock-out transactions',
        'low_stock' => 'Low-stock and out-of-stock items',
        'container_inspections' => 'Container inspection history',
        'overdue_inspections' => 'Overdue inspections',
        'container_maintenance' => 'Container maintenance and replacements',
        'user_activity' => 'User activity (owner only)',
    ];

    /** All reports return: title, columns, rows (arrays of strings), summary (label => value), chart|null. */
    public static function build(string $type, ?string $from, ?string $to, string $group = 'day'): array
    {
        $fromUtc = Fmt::dayStart($from);
        $toUtc = Fmt::dayEnd($to);
        return match ($type) {
            'ph_summary' => self::phSummary($fromUtc, $toUtc, $group),
            'ph_out_of_range' => self::phOut($fromUtc, $toUtc),
            'sensor_availability' => self::availability($fromUtc, $toUtc),
            'inventory_balances' => self::balances(),
            'stock_movements' => self::movements($fromUtc, $toUtc),
            'low_stock' => self::lowStock(),
            'container_inspections' => self::inspections($from, $to),
            'overdue_inspections' => self::overdue(),
            'container_maintenance' => self::maintenance($from, $to),
            'user_activity' => self::activity($fromUtc, $toUtc),
            default => self::empty('Report'),
        };
    }

    private static function empty(string $title): array
    {
        return ['title' => $title, 'columns' => [], 'rows' => [], 'summary' => [], 'chart' => null];
    }

    private static function range($q, ?Carbon $from, ?Carbon $to, string $col = 'measured_at')
    {
        return $q->when($from, fn ($x) => $x->where($col, '>=', $from))->when($to, fn ($x) => $x->where($col, '<=', $to));
    }

    private static function phSummary(?Carbon $from, ?Carbon $to, string $group): array
    {
        $buckets = [];
        $all = [];
        $q = self::range(Reading::whereIn('status', ['low', 'normal', 'high'])->orderBy('measured_at'), $from, $to);
        foreach ($q->cursor() as $r) {
            $local = $r->measured_at->copy()->timezone(Fmt::tz());
            [$key, $label] = match ($group) {
                'week' => [$local->format('o-W'), 'Week '.$local->format('W, o')],
                'month' => [$local->format('Y-m'), $local->format('F Y')],
                default => [$local->format('Y-m-d'), $local->format('M d, Y')],
            };
            $b = &$buckets[$key];
            $b ??= ['label' => $label, 'n' => 0, 'sum' => 0, 'min' => 99, 'max' => -99, 'out' => 0];
            $b['n']++; $b['sum'] += $r->ph_value; $b['min'] = min($b['min'], $r->ph_value); $b['max'] = max($b['max'], $r->ph_value);
            if ($r->status !== 'normal') $b['out']++;
            unset($b);
            $all[] = $r->ph_value;
        }
        ksort($buckets);
        $rows = collect($buckets)->map(fn ($b) => [$b['label'], $b['n'], Fmt::num($b['sum'] / $b['n']), Fmt::num($b['min']), Fmt::num($b['max']), $b['out']])->values()->all();
        return [
            'title' => 'pH readings by '.$group, 'columns' => ['Period', 'Readings', 'Average pH', 'Min pH', 'Max pH', 'Out of range'], 'rows' => $rows,
            'summary' => $all ? ['Readings' => count($all), 'Average pH' => Fmt::num(array_sum($all) / count($all)), 'Min pH' => Fmt::num(min($all)), 'Max pH' => Fmt::num(max($all))] : ['Readings' => 0],
            'chart' => $rows ? ['type' => 'line', 'label' => 'Average pH', 'labels' => array_column($rows, 0), 'values' => array_map(fn ($r) => (float) $r[2], $rows)] : null,
        ];
    }

    private static function phOut(?Carbon $from, ?Carbon $to): array
    {
        $q = self::range(Reading::with('device')->where('status', '!=', 'normal')->latest('measured_at'), $from, $to);
        $total = (clone $q)->count();
        $rows = $q->limit(1000)->get()->map(fn ($r) => [Fmt::dt($r->measured_at), $r->device?->device_id, $r->ph_value === null ? '—' : Fmt::num($r->ph_value), $r->status_label])->all();
        $byStatus = self::range(Reading::where('status', '!=', 'normal'), $from, $to)->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');
        return [
            'title' => 'Readings outside thresholds', 'columns' => ['Measured at', 'Device', 'pH', 'Status'], 'rows' => $rows,
            'summary' => ['Total flagged' => $total] + $byStatus->mapWithKeys(fn ($c, $s) => [Reading::STATUS[$s][0] ?? $s => $c])->all(),
            'chart' => $byStatus->isNotEmpty() ? ['type' => 'bar', 'label' => 'Readings', 'labels' => $byStatus->keys()->map(fn ($s) => Reading::STATUS[$s][0] ?? $s)->all(), 'values' => $byStatus->values()->all()] : null,
        ];
    }

    private static function availability(?Carbon $from, ?Carbon $to): array
    {
        $interval = max(10, (int) Setting::get('reporting_interval_seconds'));
        $gapLimit = max($interval * 3, 300);
        $rows = []; $summary = [];
        foreach (Device::all() as $device) {
            $q = self::range(Reading::where('device_id', $device->id)->orderBy('measured_at'), $from, $to);
            $count = 0; $prev = null; $first = null;
            foreach ($q->cursor() as $r) {
                $count++;
                $first ??= $r->measured_at;
                if ($prev && $prev->diffInSeconds($r->measured_at) > $gapLimit) {
                    $rows[] = [$device->device_id, Fmt::dt($prev), Fmt::dt($r->measured_at), round($prev->diffInSeconds($r->measured_at) / 60, 1)];
                }
                $prev = $r->measured_at;
            }
            $start = $from ?? $first; $end = $to && $to->lt(now()) ? $to : now();
            $expected = $start ? max(1, (int) floor($start->diffInSeconds($end) / $interval)) : 0;
            $summary[$device->device_id.' availability'] = $expected ? min(100, round($count / $expected * 100, 1)).'% ('.$count.' readings)' : 'No data';
        }
        return ['title' => 'Sensor availability and missing periods', 'columns' => ['Device', 'Last reading before gap', 'Next reading', 'Gap (minutes)'], 'rows' => $rows, 'summary' => $summary, 'chart' => null];
    }

    private static function balances(): array
    {
        $items = InventoryItem::with(['category', 'supplier'])->where('status', 'active')->orderBy('name')->get();
        $rows = $items->map(fn ($i) => [$i->sku, $i->name, $i->category?->name, Fmt::qty($i->quantity).' '.$i->unit, Fmt::qty($i->min_stock), $i->stock_label, $i->supplier?->name, $i->unit_cost === null ? '—' : Fmt::num($i->quantity * $i->unit_cost)])->all();
        $byCat = $items->groupBy(fn ($i) => $i->category?->name ?? 'Uncategorized')->map->count();
        return [
            'title' => 'Inventory balances', 'columns' => ['SKU', 'Item', 'Category', 'On hand', 'Minimum', 'Status', 'Supplier', 'Value (PHP)'], 'rows' => $rows,
            'summary' => ['Active items' => $items->count(), 'Low stock' => $items->where('stock_state', 'low')->count(), 'Out of stock' => $items->where('stock_state', 'out')->count(), 'Total value (PHP)' => Fmt::num($items->sum(fn ($i) => $i->quantity * ($i->unit_cost ?? 0)))],
            'chart' => $byCat->isNotEmpty() ? ['type' => 'bar', 'label' => 'Items', 'labels' => $byCat->keys()->all(), 'values' => $byCat->values()->all()] : null,
        ];
    }

    private static function movements(?Carbon $from, ?Carbon $to): array
    {
        $q = self::range(StockMovement::with(['item', 'user'])->latest(), $from, $to, 'created_at');
        $list = $q->limit(2000)->get();
        $rows = $list->map(fn ($m) => [Fmt::dt($m->created_at), $m->item?->name, strtoupper($m->type), Fmt::qty($m->quantity_change), Fmt::qty($m->quantity_before), Fmt::qty($m->quantity_after), $m->reason, $m->user?->name])->all();
        $counts = $list->groupBy('type')->map->count();
        return [
            'title' => 'Stock movements', 'columns' => ['Date', 'Item', 'Type', 'Change', 'Before', 'After', 'Reason', 'By'], 'rows' => $rows,
            'summary' => ['Transactions' => $list->count(), 'Stock-in lines' => $counts['in'] ?? 0, 'Stock-out lines' => $counts['out'] ?? 0, 'Adjustments' => $counts['adjust'] ?? 0],
            'chart' => $list->isNotEmpty() ? ['type' => 'bar', 'label' => 'Transactions', 'labels' => ['Stock in', 'Stock out', 'Adjustments'], 'values' => [$counts['in'] ?? 0, $counts['out'] ?? 0, $counts['adjust'] ?? 0]] : null,
        ];
    }

    private static function lowStock(): array
    {
        $items = InventoryItem::with('supplier')->where('status', 'active')->whereColumn('quantity', '<=', 'min_stock')->orderBy('quantity')->get();
        return [
            'title' => 'Low-stock and out-of-stock items', 'columns' => ['SKU', 'Item', 'On hand', 'Minimum', 'Status', 'Supplier'],
            'rows' => $items->map(fn ($i) => [$i->sku, $i->name, Fmt::qty($i->quantity).' '.$i->unit, Fmt::qty($i->min_stock), $i->stock_label, $i->supplier?->name])->all(),
            'summary' => ['Low stock' => $items->where('stock_state', 'low')->count(), 'Out of stock' => $items->where('stock_state', 'out')->count()], 'chart' => null,
        ];
    }

    private static function inspections(?string $from, ?string $to): array
    {
        $q = ContainerInspection::with(['container', 'inspector'])->when($from, fn ($x) => $x->whereDate('inspected_at', '>=', $from))->when($to, fn ($x) => $x->whereDate('inspected_at', '<=', $to))->orderByDesc('inspected_at');
        $list = $q->limit(2000)->get();
        $by = $list->groupBy('result')->map->count();
        return [
            'title' => 'Container inspection history', 'columns' => ['Date', 'Container', 'Inspector', 'Result', 'Condition', 'Findings', 'Corrective action'],
            'rows' => $list->map(fn ($i) => [Fmt::d($i->inspected_at), $i->container?->code, $i->inspector?->name, ucfirst(str_replace('_', ' ', $i->result)), ucfirst($i->condition), $i->findings, $i->corrective_action])->all(),
            'summary' => ['Inspections' => $list->count()] + $by->mapWithKeys(fn ($c, $r) => [ucfirst(str_replace('_', ' ', $r)) => $c])->all(),
            'chart' => $by->isNotEmpty() ? ['type' => 'bar', 'label' => 'Inspections', 'labels' => $by->keys()->map(fn ($r) => ucfirst(str_replace('_', ' ', $r)))->all(), 'values' => $by->values()->all()] : null,
        ];
    }

    private static function overdue(): array
    {
        $list = Container::with('assignee')->where('status', '!=', 'retired')->whereDate('next_inspection_date', '<', today())->orderBy('next_inspection_date')->get();
        return [
            'title' => 'Overdue inspections', 'columns' => ['Container', 'Type', 'Status', 'Last inspected', 'Was due', 'Days overdue', 'Assigned to'],
            'rows' => $list->map(fn ($c) => [$c->code, $c->type, $c->status_label, Fmt::d($c->last_inspection_date), Fmt::d($c->next_inspection_date), (int) $c->next_inspection_date->diffInDays(today()), $c->assignee?->name])->all(),
            'summary' => ['Overdue containers' => $list->count()], 'chart' => null,
        ];
    }

    private static function maintenance(?string $from, ?string $to): array
    {
        $list = ContainerMaintenanceRecord::with(['container', 'user'])->when($from, fn ($x) => $x->whereDate('performed_at', '>=', $from))->when($to, fn ($x) => $x->whereDate('performed_at', '<=', $to))->orderByDesc('performed_at')->limit(2000)->get();
        return [
            'title' => 'Container maintenance and replacements', 'columns' => ['Date', 'Container', 'Type', 'Description', 'Cost (PHP)', 'Recorded by'],
            'rows' => $list->map(fn ($m) => [Fmt::d($m->performed_at), $m->container?->code, ucfirst($m->type), $m->description, $m->cost === null ? '—' : Fmt::num($m->cost), $m->user?->name])->all(),
            'summary' => ['Records' => $list->count(), 'Total cost (PHP)' => Fmt::num($list->sum('cost'))], 'chart' => null,
        ];
    }

    private static function activity(?Carbon $from, ?Carbon $to): array
    {
        $list = self::range(AuditLog::with('user')->latest('created_at'), $from, $to, 'created_at')->limit(2000)->get();
        return [
            'title' => 'User activity', 'columns' => ['Date', 'User', 'Action', 'Record', 'IP'],
            'rows' => $list->map(fn ($l) => [Fmt::dt($l->created_at), $l->user?->name ?? 'System / guest', $l->action, trim(($l->subject_type ?? '').' '.($l->subject_label ?? '')), $l->ip])->all(),
            'summary' => ['Events' => $list->count()], 'chart' => null,
        ];
    }
}
