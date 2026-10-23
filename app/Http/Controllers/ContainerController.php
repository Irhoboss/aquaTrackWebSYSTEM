<?php

namespace App\Http\Controllers;

use App\Models\Container;
use App\Models\ContainerInspection;
use App\Models\ContainerMaintenanceRecord;
use App\Models\User;
use App\Services\AlertService;
use App\Support\Audit;
use App\Support\Csv;
use App\Support\Fmt;
use App\Support\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ContainerController extends Controller
{
    private function query(Request $request)
    {
        return Container::with('assignee')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->condition, fn ($q, $c) => $q->where('condition', $c))
            ->when($request->overdue, fn ($q) => $q->where('status', '!=', 'retired')->whereDate('next_inspection_date', '<', today()))
            ->when($request->replacement, fn ($q) => $q->where('status', '!=', 'retired')->whereDate('replacement_due_date', '<=', today()))
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('code', 'like', "%{$s}%")->orWhere('type', 'like', "%{$s}%")->orWhere('notes', 'like', "%{$s}%")))
            ->orderBy('code');
    }

    public function index(Request $request)
    {
        return view('containers.index', ['containers' => $this->query($request)->paginate(15)->withQueryString()]);
    }

    public function export(Request $request)
    {
        $rows = (function () use ($request) {
            foreach ($this->query($request)->cursor() as $c) {
                yield [$c->code, $c->type, $c->capacity_liters, Fmt::d($c->date_acquired), $c->status_label, ucfirst($c->condition), Fmt::d($c->last_inspection_date),
                    Fmt::d($c->next_inspection_date), $c->inspection_frequency_days, Fmt::d($c->replacement_due_date), $c->assignee?->name, $c->notes];
            }
        })();
        return Csv::download('containers-'.now()->format('Ymd').'.csv',
            ['Code', 'Type', 'Capacity (L)', 'Acquired', 'Status', 'Condition', 'Last inspection', 'Next inspection', 'Frequency (days)', 'Replacement due', 'Assigned to', 'Notes'], $rows);
    }

    public function create()
    {
        return view('containers.form', ['container' => new Container(['status' => 'available', 'condition' => 'good', 'inspection_frequency_days' => Setting::get('default_inspection_days')]), 'users' => User::where('is_active', true)->orderBy('name')->get()]);
    }

    private function rules(?Container $c = null): array
    {
        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('containers', 'code')->ignore($c?->id)],
            'type' => 'required|string|max:60',
            'capacity_liters' => 'nullable|numeric|min:0|max:100000',
            'date_acquired' => 'nullable|date|before_or_equal:today',
            'status' => ['required', Rule::in(array_keys(Container::STATUSES))],
            'condition' => ['required', Rule::in(array_keys(Container::CONDITIONS))],
            'inspection_frequency_days' => 'required|integer|min:1|max:3650',
            'next_inspection_date' => 'nullable|date',
            'replacement_due_date' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $data['next_inspection_date'] ??= now()->addDays((int) $data['inspection_frequency_days'])->toDateString();
        if (empty($data['replacement_due_date']) && !empty($data['date_acquired'])) {
            $data['replacement_due_date'] = \Carbon\Carbon::parse($data['date_acquired'])->addMonths((int) Setting::get('default_replacement_months'))->toDateString();
        }
        $container = Container::create($data);
        AlertService::syncContainer($container->fresh());
        Audit::log('container.created', $container);
        return redirect()->route('containers.show', $container)->with('success', 'Container registered.');
    }

    public function show(Container $container)
    {
        return view('containers.show', [
            'container' => $container->load('assignee'),
            'inspections' => $container->inspections()->with('inspector')->get(),
            'records' => $container->maintenanceRecords()->with('user')->get(),
        ]);
    }

    public function edit(Container $container)
    {
        return view('containers.form', ['container' => $container, 'users' => User::where('is_active', true)->orderBy('name')->get()]);
    }

    public function update(Request $request, Container $container)
    {
        $container->update($request->validate($this->rules($container))); // history tables are untouched
        AlertService::syncContainer($container->fresh());
        Audit::log('container.updated', $container, ['status' => $container->status]);
        return redirect()->route('containers.show', $container)->with('success', 'Container updated.');
    }

    public function inspect(Request $request, Container $container)
    {
        if ($container->status === 'retired') {
            return back()->with('error', 'Retired containers cannot be inspected.');
        }
        $data = $request->validate([
            'inspected_at' => 'required|date|before_or_equal:today',
            'result' => 'required|in:pass,needs_attention,fail',
            'condition' => ['required', Rule::in(array_keys(Container::CONDITIONS))],
            'findings' => 'nullable|string|max:1500',
            'corrective_action' => 'required_unless:result,pass|nullable|string|max:1500',
            'next_inspection_date' => 'nullable|date|after_or_equal:inspected_at',
        ]);

        DB::transaction(function () use ($container, $data, $request) {
            $next = $data['next_inspection_date'] ?? \Carbon\Carbon::parse($data['inspected_at'])->addDays($container->inspection_frequency_days)->toDateString();
            ContainerInspection::create($data + ['container_id' => $container->id, 'inspected_by' => $request->user()->id, 'next_inspection_date' => $next]);

            $status = match (true) {
                $data['result'] === 'fail' => 'under_maintenance',
                $data['result'] === 'pass' && $container->status === 'needs_inspection' => 'available',
                default => $container->status,
            };
            $container->update(['last_inspection_date' => $data['inspected_at'], 'next_inspection_date' => $next, 'condition' => $data['condition'], 'status' => $status]);
        });

        AlertService::syncContainer($container->fresh());
        Audit::log('container.inspected', $container, ['result' => $data['result']]);
        return back()->with('success', 'Inspection recorded.');
    }

    public function maintenance(Request $request, Container $container)
    {
        $data = $request->validate([
            'type' => 'required|in:maintenance,repair,replacement',
            'performed_at' => 'required|date|before_or_equal:today',
            'description' => 'nullable|string|max:1500',
            'cost' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($container, $data, $request) {
            ContainerMaintenanceRecord::create($data + ['container_id' => $container->id, 'user_id' => $request->user()->id]);
            if ($data['type'] === 'replacement') {
                $container->update(['status' => 'retired']); // keeps all history; register the new container separately
            } elseif (in_array($container->status, ['under_maintenance', 'for_replacement'])) {
                $container->update(['status' => 'available']);
            }
        });

        AlertService::syncContainer($container->fresh());
        Audit::log('container.'.$data['type'], $container);
        return back()->with('success', $data['type'] === 'replacement' ? 'Replacement recorded. The container is now retired; register the new container separately.' : 'Maintenance record saved.');
    }
}
