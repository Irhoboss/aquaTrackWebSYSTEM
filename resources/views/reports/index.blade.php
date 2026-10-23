@extends('layouts.app')
@section('title', 'Reports')
@section('subtitle', 'All figures are calculated from recorded data')
@section('content')
<div class="at-card card-body-pad mb-3">
  <form class="row g-2 align-items-end" method="get">
    <div class="col-12 col-md-4"><label class="form-label">Report</label><select name="type" class="form-select">@foreach($types as $k => $v)<option value="{{ $k }}" @selected($type === $k)>{{ $v }}</option>@endforeach</select></div>
    <div class="col-6 col-md-2"><label class="form-label">From</label><input type="date" name="from" value="{{ $from }}" class="form-control"></div>
    <div class="col-6 col-md-2"><label class="form-label">To</label><input type="date" name="to" value="{{ $to }}" class="form-control"></div>
    <div class="col-6 col-md-2"><label class="form-label">Group pH by</label><select name="group" class="form-select">@foreach(['day', 'week', 'month'] as $g)<option value="{{ $g }}" @selected($group === $g)>{{ ucfirst($g) }}</option>@endforeach</select></div>
    <div class="col-6 col-md-2"><button class="btn btn-primary w-100">Generate</button></div>
  </form>
</div>

<div class="d-flex gap-2 mb-3 no-print">
  <a class="btn btn-soft" href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"><i class="bi bi-download me-1"></i>Download CSV</a>
  <a class="btn btn-soft" target="_blank" href="{{ request()->fullUrlWithQuery(['print' => 1]) }}"><i class="bi bi-printer me-1"></i>Print version</a>
</div>

<div class="row g-2 mb-3">
  @foreach($report['summary'] as $label => $value)<div class="col-6 col-md-3"><div class="at-tile"><div class="v">{{ $value }}</div><div class="l">{{ $label }}</div></div></div>@endforeach
</div>

@if($report['chart'])
<div class="at-card card-body-pad mb-3"><div style="height:260px"><canvas id="chart"></canvas></div></div>
@endif

<div class="at-card"><div class="card-head"><h2>{{ $report['title'] }}</h2></div>
  <div class="table-responsive"><table class="table table-hover"><thead><tr>@foreach($report['columns'] as $c)<th>{{ $c }}</th>@endforeach</tr></thead><tbody>
  @forelse($report['rows'] as $row)<tr>@foreach($row as $cell)<td class="small">{{ $cell }}</td>@endforeach</tr>
  @empty<tr><td colspan="{{ max(1, count($report['columns'])) }}">@include('partials.empty', ['icon' => 'bi-bar-chart', 'title' => 'No data for this report', 'text' => 'Try a wider date range.'])</td></tr>@endforelse
  </tbody></table></div></div>
@if(str_starts_with($type, 'ph_') || $type === 'sensor_availability')<div class="mt-3">@include('partials.disclaimer')</div>@endif
@endsection

@if($report['chart'])
@push('scripts')
<script>
const c = @json($report['chart']);
new Chart(document.getElementById('chart'), { type: c.type, data: { labels: c.labels, datasets: [{ label: c.label, data: c.values, borderColor: '#14998b', backgroundColor: c.type === 'bar' ? 'rgba(43,196,180,.55)' : 'rgba(43,196,180,.15)', fill: c.type === 'line', tension: .3, borderRadius: 8 }] },
  options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: c.type === 'bar' } } } });
</script>
@endpush
@endif
