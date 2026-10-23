@extends('layouts.app')
@section('title', $container->code)
@section('subtitle', $container->type)
@section('content')
<div class="row g-3">
  <div class="col-lg-4 vstack gap-3">
    <div class="at-card card-body-pad">
      <div class="d-flex justify-content-between"><span class="pill pill-{{ $container->status_class }}">{{ $container->status_label }}</span><span class="pill pill-muted">{{ ucfirst($container->condition) }}</span></div>
      <dl class="row small mt-3 mb-0">
        <dt class="col-6">Capacity</dt><dd class="col-6">{{ $container->capacity_liters ? $container->capacity_liters.' L' : '—' }}</dd>
        <dt class="col-6">Acquired</dt><dd class="col-6">@d($container->date_acquired)</dd>
        <dt class="col-6">Last inspection</dt><dd class="col-6">@d($container->last_inspection_date)</dd>
        <dt class="col-6">Next inspection</dt><dd class="col-6">@d($container->next_inspection_date) @if($container->is_inspection_overdue)<span class="pill pill-bad">Overdue</span>@endif</dd>
        <dt class="col-6">Frequency</dt><dd class="col-6">Every {{ $container->inspection_frequency_days }} days</dd>
        <dt class="col-6">Replacement due</dt><dd class="col-6">@d($container->replacement_due_date) @if($container->is_replacement_due)<span class="pill pill-bad">Due</span>@endif</dd>
        <dt class="col-6">Assigned to</dt><dd class="col-6">{{ $container->assignee?->name ?? '—' }}</dd>
        <dt class="col-12">Notes</dt><dd class="col-12 mb-0">{{ $container->notes ?: '—' }}</dd>
      </dl>
      @can('containers.manage')<a class="btn btn-soft btn-sm mt-3" href="{{ route('containers.edit', $container) }}"><i class="bi bi-pencil me-1"></i>Edit / update status</a>@endcan
    </div>

    @can('inspections.record')
    @if($container->status !== 'retired')
    <div class="at-card card-body-pad"><h2 class="h6 mb-3">Record inspection</h2>
      <form method="post" action="{{ route('containers.inspect', $container) }}" class="row g-2">@csrf
        <div class="col-6"><label class="form-label">Date</label><input type="date" name="inspected_at" value="{{ old('inspected_at', now(\App\Support\Fmt::tz())->toDateString()) }}" max="{{ now(\App\Support\Fmt::tz())->toDateString() }}" class="form-control" required></div>
        <div class="col-6"><label class="form-label">Result</label><select name="result" class="form-select"><option value="pass">Pass</option><option value="needs_attention" @selected(old('result') === 'needs_attention')>Needs attention</option><option value="fail" @selected(old('result') === 'fail')>Fail</option></select></div>
        <div class="col-12"><label class="form-label">Condition</label><select name="condition" class="form-select">@foreach(\App\Models\Container::CONDITIONS as $k => $v)<option value="{{ $k }}" @selected(old('condition', $container->condition) === $k)>{{ $v }}</option>@endforeach</select></div>
        <div class="col-12"><label class="form-label">Findings</label><textarea name="findings" rows="2" class="form-control">{{ old('findings') }}</textarea></div>
        <div class="col-12"><label class="form-label">Corrective action <span class="text-muted fw-normal">(required unless pass)</span></label><textarea name="corrective_action" rows="2" class="form-control">{{ old('corrective_action') }}</textarea></div>
        <div class="col-12"><label class="form-label">Next inspection <span class="text-muted fw-normal">(optional)</span></label><input type="date" name="next_inspection_date" value="{{ old('next_inspection_date') }}" class="form-control"></div>
        <div class="col-12"><button class="btn btn-primary w-100">Save inspection</button></div>
      </form></div>
    @endif
    @endcan

    @can('containers.manage')
    <div class="at-card card-body-pad"><h2 class="h6 mb-3">Record maintenance / replacement</h2>
      <form method="post" action="{{ route('containers.maintenance', $container) }}" class="row g-2" data-confirm="Save this record? A replacement retires this container.">@csrf
        <div class="col-6"><label class="form-label">Type</label><select name="type" class="form-select"><option value="maintenance">Maintenance</option><option value="repair">Repair</option><option value="replacement">Replacement (retires)</option></select></div>
        <div class="col-6"><label class="form-label">Date</label><input type="date" name="performed_at" value="{{ now(\App\Support\Fmt::tz())->toDateString() }}" class="form-control" required></div>
        <div class="col-12"><label class="form-label">Description</label><textarea name="description" rows="2" class="form-control"></textarea></div>
        <div class="col-12"><label class="form-label">Cost (PHP)</label><input type="number" step="0.01" min="0" name="cost" class="form-control"></div>
        <div class="col-12"><button class="btn btn-soft w-100">Save record</button></div>
      </form></div>
    @endcan
  </div>

  <div class="col-lg-8 vstack gap-3">
    <div class="at-card"><div class="card-head"><h2>Inspection history</h2></div>
      @forelse($inspections as $i)
        <div class="px-3 py-3 border-top" style="border-color:var(--at-line)!important">
          <div class="d-flex flex-wrap gap-2 align-items-center"><strong>@d($i->inspected_at)</strong>
            <span class="pill pill-{{ ['pass' => 'ok', 'needs_attention' => 'warn', 'fail' => 'bad'][$i->result] }}">{{ ucfirst(str_replace('_', ' ', $i->result)) }}</span>
            <span class="pill pill-muted">{{ ucfirst($i->condition) }}</span><span class="small text-muted ms-auto">{{ $i->inspector?->name ?? '—' }}</span></div>
          @if($i->findings)<div class="small mt-1"><strong>Findings:</strong> {{ $i->findings }}</div>@endif
          @if($i->corrective_action)<div class="small"><strong>Corrective action:</strong> {{ $i->corrective_action }}</div>@endif
        </div>
      @empty @include('partials.empty', ['icon' => 'bi-clipboard2', 'title' => 'No inspections recorded yet']) @endforelse
    </div>
    <div class="at-card"><div class="card-head"><h2>Maintenance and replacement history</h2></div>
      @forelse($records as $r)
        <div class="px-3 py-2 border-top d-flex justify-content-between" style="border-color:var(--at-line)!important"><div><strong>{{ ucfirst($r->type) }}</strong> · @d($r->performed_at)<div class="small text-muted">{{ $r->description }}</div></div><div class="small text-end">{{ $r->cost !== null ? 'PHP '.number_format($r->cost, 2) : '' }}<div class="text-muted">{{ $r->user?->name }}</div></div></div>
      @empty @include('partials.empty', ['icon' => 'bi-wrench', 'title' => 'No maintenance records']) @endforelse
    </div>
  </div>
</div>
@endsection
