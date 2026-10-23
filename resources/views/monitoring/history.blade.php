@extends('layouts.app')
@section('title', 'Reading history')
@section('subtitle', 'Search, filter, export and print pH readings')
@section('content')
<div class="at-card card-body-pad mb-3">
  <form class="row g-2 align-items-end" method="get">
    <div class="col-12 col-md-3"><label class="form-label">Search</label><input name="q" value="{{ request('q') }}" class="form-control" placeholder="pH value or device"></div>
    <div class="col-6 col-md-2"><label class="form-label">Status</label><select name="status" class="form-select"><option value="">All</option>@foreach(\App\Models\Reading::STATUS as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v[0] }}</option>@endforeach</select></div>
    <div class="col-6 col-md-2"><label class="form-label">Device</label><select name="device_id" class="form-select"><option value="">All</option>@foreach($devices as $d)<option value="{{ $d->id }}" @selected(request('device_id') == $d->id)>{{ $d->name }}</option>@endforeach</select></div>
    <div class="col-6 col-md-2"><label class="form-label">From</label><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
    <div class="col-6 col-md-2"><label class="form-label">To</label><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
    <div class="col-12 col-md-1 d-flex gap-1"><button class="btn btn-primary flex-grow-1" aria-label="Apply filters"><i class="bi bi-funnel"></i></button></div>
    <div class="col-12 d-flex gap-2 flex-wrap">
      <a class="btn btn-soft btn-sm" href="{{ route('readings.index') }}">Reset filters</a>
      <a class="btn btn-soft btn-sm" href="{{ route('readings.export', request()->query()) }}"><i class="bi bi-download me-1"></i>CSV</a>
      <a class="btn btn-soft btn-sm" target="_blank" href="{{ route('readings.print', request()->query()) }}"><i class="bi bi-printer me-1"></i>Print</a>
    </div>
  </form>
</div>

<div class="at-card">
  <div class="table-responsive"><table class="table table-hover">
    <thead><tr><th>@include('partials.sort', ['col' => 'measured_at', 'label' => 'Measured'])</th><th>Device</th><th>@include('partials.sort', ['col' => 'ph_value', 'label' => 'pH'])</th><th>@include('partials.sort', ['col' => 'status', 'label' => 'Status'])</th><th>Sensor</th><th></th></tr></thead>
    <tbody>
    @forelse($readings as $r)
      <tr class="{{ in_array($r->status, ['high', 'sensor_error']) ? 'row-bad' : ($r->status === 'low' ? 'row-warn' : '') }}">
        <td>@dt($r->measured_at)</td><td>{{ $r->device?->name }}</td><td class="fw-bold">{{ $r->ph_value === null ? '—' : number_format($r->ph_value, 2) }}</td>
        <td><span class="pill pill-{{ $r->status_class }}">{{ $r->status_label }}</span></td><td class="small text-muted">{{ $r->sensor_status }}</td>
        <td class="text-end"><a class="btn-circle btn-sm" style="width:32px;height:32px" href="{{ route('readings.show', $r) }}" aria-label="View reading"><i class="bi bi-arrow-right"></i></a></td></tr>
    @empty
      <tr><td colspan="6">@include('partials.empty', ['icon' => 'bi-search', 'title' => 'No readings match', 'text' => 'Try a different date range or reset the filters.'])</td></tr>
    @endforelse
    </tbody></table></div>
  <div class="px-3 pt-3">{{ $readings->links() }}</div>
</div>
@endsection
