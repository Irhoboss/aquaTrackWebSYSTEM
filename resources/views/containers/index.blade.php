@extends('layouts.app')
@section('title', 'Containers')
@section('subtitle', 'Inspection and replacement tracking')
@section('content')
<div class="at-card card-body-pad mb-3">
  <form class="row g-2 align-items-end" method="get">
    <div class="col-12 col-md-3"><label class="form-label">Search</label><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Code, type or notes"></div>
    <div class="col-6 col-md-2"><label class="form-label">Status</label><select name="status" class="form-select"><option value="">All</option>@foreach(\App\Models\Container::STATUSES as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach</select></div>
    <div class="col-6 col-md-2"><label class="form-label">Condition</label><select name="condition" class="form-select"><option value="">All</option>@foreach(\App\Models\Container::CONDITIONS as $k => $v)<option value="{{ $k }}" @selected(request('condition') === $k)>{{ $v }}</option>@endforeach</select></div>
    <div class="col-12 col-md-3 d-flex gap-3 pb-2"><div class="form-check"><input class="form-check-input" type="checkbox" name="overdue" value="1" id="od" @checked(request('overdue'))><label class="form-check-label small" for="od">Overdue inspection</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="replacement" value="1" id="rp" @checked(request('replacement'))><label class="form-check-label small" for="rp">Replacement due</label></div></div>
    <div class="col-12 col-md-2 d-flex gap-2"><button class="btn btn-primary flex-grow-1">Filter</button><a class="btn btn-soft" href="{{ route('containers.index') }}">Reset</a></div>
  </form>
</div>
<div class="d-flex flex-wrap gap-2 mb-3">
  @can('containers.manage')<a class="btn btn-primary" href="{{ route('containers.create') }}"><i class="bi bi-plus-lg me-1"></i>Register container</a>@endcan
  <a class="btn btn-soft" href="{{ route('containers.export', request()->query()) }}"><i class="bi bi-download me-1"></i>CSV</a>
  <a class="btn btn-soft" target="_blank" href="{{ route('reports.index', ['type' => 'container_inspections', 'print' => 1]) }}"><i class="bi bi-printer me-1"></i>Inspection report</a>
</div>
<div class="at-card"><div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Container</th><th>Status</th><th>Condition</th><th>Last inspection</th><th>Next inspection</th><th>Replacement</th><th>Assigned</th><th></th></tr></thead><tbody>
@forelse($containers as $c)
  <tr class="{{ $c->is_inspection_overdue || $c->is_replacement_due ? 'row-warn' : '' }}">
    <td class="fw-bold">{{ $c->code }}<div class="small fw-normal text-muted">{{ $c->type }} @if($c->capacity_liters)· {{ $c->capacity_liters }} L @endif</div></td>
    <td><span class="pill pill-{{ $c->status_class }}">{{ $c->status_label }}</span></td><td>{{ ucfirst($c->condition) }}</td>
    <td class="small">@d($c->last_inspection_date)</td>
    <td class="small">@d($c->next_inspection_date) @if($c->is_inspection_overdue)<span class="pill pill-bad">Overdue</span>@endif</td>
    <td class="small">@d($c->replacement_due_date) @if($c->is_replacement_due)<span class="pill pill-bad">Due</span>@endif</td>
    <td class="small">{{ $c->assignee?->name ?? '—' }}</td>
    <td class="text-end"><a class="btn-circle" href="{{ route('containers.show', $c) }}" aria-label="Open {{ $c->code }}"><i class="bi bi-arrow-right"></i></a></td></tr>
@empty<tr><td colspan="8">@include('partials.empty', ['icon' => 'bi-bucket', 'title' => 'No containers found', 'text' => 'Register a container to start tracking inspections.'])</td></tr>@endforelse
</tbody></table></div><div class="px-3 pt-3">{{ $containers->links() }}</div></div>
@endsection
