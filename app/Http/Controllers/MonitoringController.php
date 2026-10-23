<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Reading;
use App\Services\DashboardService;
use App\Support\Csv;
use App\Support\Fmt;
use App\Support\Setting;
use Illuminate\Http\Request;

class MonitoringController extends Controller
{
    public function index()
    {
        return view('monitoring.index', ['devices' => Device::orderBy('name')->get()]);
    }

    /** Polled JSON for the monitoring page. */
    public function live(Request $request)
    {
        $hours = ['1h' => 1, '24h' => 24, '7d' => 168, '30d' => 720][$request->input('range', '24h')] ?? 24;
        $since = now()->subHours($hours);

        $valid = Reading::where('measured_at', '>=', $since)->whereIn('status', ['low', 'normal', 'high']);
        $agg = (clone $valid)->selectRaw('min(ph_value) as mn, max(ph_value) as mx, avg(ph_value) as av, count(*) as c')->first();
        $points = (clone $valid)->orderBy('measured_at')->get(['measured_at', 'ph_value']);
        if ($points->count() > 300) {
            $step = (int) ceil($points->count() / 300);
            $points = $points->values()->filter(fn ($p, $i) => $i % $step === 0)->values();
        }
        $latest = Reading::with('device')->latest('measured_at')->first();

        return response()->json([
            'latest' => $latest ? DashboardService::readingRow($latest) + ['temperature' => $latest->temperature, 'voltage' => $latest->raw_voltage_mv] : null,
            'stats' => ['min' => $agg->mn, 'max' => $agg->mx, 'avg' => $agg->av !== null ? round($agg->av, 2) : null, 'count' => (int) $agg->c],
            'chart' => [
                'labels' => $points->map(fn ($p) => Fmt::dt($p->measured_at, $hours > 24 ? 'M d H:i' : 'H:i'))->all(),
                'values' => $points->map(fn ($p) => $p->ph_value)->all(),
            ],
            'devices' => Device::orderBy('name')->get()->map(fn ($d) => [
                'name' => $d->name, 'device_id' => $d->device_id, 'online' => $d->isOnline(), 'active' => $d->is_active,
                'last_seen' => $d->last_seen_at ? Fmt::dt($d->last_seen_at) : 'Never',
            ])->all(),
            'recent' => Reading::with('device')->latest('measured_at')->limit(10)->get()->map(fn ($r) => DashboardService::readingRow($r))->all(),
            'ph_min' => (float) Setting::get('ph_min'),
            'ph_max' => (float) Setting::get('ph_max'),
        ]);
    }

    private function filtered(Request $request)
    {
        $sorts = ['measured_at', 'ph_value', 'status'];
        $sort = in_array($request->sort, $sorts) ? $request->sort : 'measured_at';
        $dir = $request->dir === 'asc' ? 'asc' : 'desc';

        return Reading::with('device')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->device_id, fn ($q, $d) => $q->where('device_id', $d))
            ->when($request->from, fn ($q) => $q->where('measured_at', '>=', Fmt::dayStart($request->from)))
            ->when($request->to, fn ($q) => $q->where('measured_at', '<=', Fmt::dayEnd($request->to)))
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('ph_value', 'like', "%{$s}%")
                ->orWhereHas('device', fn ($d) => $d->where('name', 'like', "%{$s}%")->orWhere('device_id', 'like', "%{$s}%"))))
            ->orderBy($sort, $dir);
    }

    public function history(Request $request)
    {
        return view('monitoring.history', [
            'readings' => $this->filtered($request)->paginate(20)->withQueryString(),
            'devices' => Device::orderBy('name')->get(),
        ]);
    }

    public function show(Reading $reading)
    {
        return view('monitoring.show', ['reading' => $reading->load('device')]);
    }

    public function export(Request $request)
    {
        $rows = (function () use ($request) {
            foreach ($this->filtered($request)->cursor() as $r) {
                yield [$r->id, $r->device?->device_id, $r->ph_value, $r->status_label, $r->sensor_status,
                    $r->temperature, $r->raw_voltage_mv, Fmt::dt($r->measured_at, 'Y-m-d H:i:s'), Fmt::dt($r->received_at, 'Y-m-d H:i:s')];
            }
        })();
        return Csv::download('ph-readings-'.now()->format('Ymd-His').'.csv',
            ['ID', 'Device', 'pH', 'Status', 'Sensor status', 'Temperature', 'Voltage (mV)', 'Measured (PH time)', 'Received (PH time)'], $rows);
    }

    public function print(Request $request)
    {
        return view('monitoring.print', ['readings' => $this->filtered($request)->limit(500)->get(), 'filters' => $request->only('from', 'to', 'status', 'device_id', 'q')]);
    }
}
