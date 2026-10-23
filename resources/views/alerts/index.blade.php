@extends('layouts.app')
@section('title', 'Alerts')
@section('subtitle', 'Acknowledge and resolve issues')
@section('content')
<div class="at-card card-body-pad mb-3">
  <form class="row g-2 align-items-end" method="get">
    <div class="col-12 col-md-3"><label class="form-label">Search</label><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Title, item, device or #ID"></div>
    <div class="col-6 col-md-2"><label class="form-label">Status</label><select name="status" class="form-select"><option value="">All</option>@foreach(['new' => 'New', 'acknowledged' => 'Acknowledged', 'resolved' => 'Resolved'] as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach</select></div>
    <div class="col-6 col-md-2"><label class="form-label">Severity</label><select name="severity" class="form-select"><option value="">All</option>@foreach(['info', 'warning', 'critical'] as $v)<option value="{{ $v }}" @selected(request('severity') === $v)>{{ ucfirst($v) }}</option>@endforeach</select></div>
    <div class="col-6 col-md-2"><label class="form-label">From</label><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
    <div class="col-6 col-md-2"><label class="form-label">To</label><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
    <div class="col-12 col-md-1"><button class="btn btn-primary w-100" aria-label="Apply filters"><i class="bi bi-funnel"></i></button></div>
    <div class="col-12"><a class="btn btn-soft btn-sm" href="{{ route('alerts.index') }}">Reset filters</a></div>
  </form>
</div>

<div class="vstack gap-3">
@forelse($alerts as $a)
  <div class="at-card card-body-pad">
    <div class="d-flex flex-wrap gap-2 align-items-center mb-1">
      <span class="text-muted small fw-bold">#{{ $a->id }}</span>
      <span class="pill pill-{{ $a->severity_class }}">{{ ucfirst($a->severity) }}</span>
      <span class="pill pill-muted">{{ $a->type_label }}</span>
      <span class="pill pill-{{ $a->status_class }} ms-auto">{{ ucfirst($a->status) }}</span>
    </div>
    <div class="fw-bold fs-6">{{ $a->title }}</div>
    <div class="text-muted">{{ $a->description }}</div>
    <div class="small text-muted mt-1">
      @if($a->related_label)Related: {{ $a->related_label }} · @endif Created @dt($a->created_at)
      @if($a->occurrences > 1) · Repeated {{ $a->occurrences }}× (last @dt($a->last_seen_at))@endif
      @if($a->acknowledger) · Acknowledged by {{ $a->acknowledger->name }} @endif
      @if($a->resolved_at) · Resolved @dt($a->resolved_at) {{ $a->resolver ? 'by '.$a->resolver->name : '(automatically)' }}@endif
    </div>
    @if($a->resolution_notes)<div class="small mt-1"><strong>Resolution notes:</strong> {{ $a->resolution_notes }}</div>@endif

    @can('alerts.manage')
      @if($a->status !== 'resolved')
      <div class="d-flex flex-wrap gap-2 mt-3">
        @if($a->status === 'new')<form method="post" action="{{ route('alerts.acknowledge', $a) }}">@csrf<button class="btn btn-soft btn-sm">Acknowledge</button></form>@endif
        <form method="post" action="{{ route('alerts.resolve', $a) }}" class="d-flex gap-2 flex-grow-1" style="min-width:260px">@csrf
          <input name="resolution_notes" class="form-control form-control-sm" placeholder="What was done to resolve this?" required maxlength="1000">
          <button class="btn btn-primary btn-sm">Resolve</button></form>
      </div>
      @endif
    @endcan
  </div>
@empty
  <div class="at-card">@include('partials.empty', ['icon' => 'bi-check2-circle', 'title' => 'No alerts found', 'text' => 'Nothing matches these filters.'])</div>
@endforelse
</div>
<div class="mt-3">{{ $alerts->links() }}</div>
@endsection
