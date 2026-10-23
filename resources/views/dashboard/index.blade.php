@extends('layouts.app')
@section('title', 'Dashboard')
@section('subtitle', 'Good day, '.explode(' ', auth()->user()->name)[0])
@section('content')
@php $can = $stats['can_monitor']; @endphp

<div class="row g-3 mb-3">
  @if($can)
  <div class="col-lg-5">
    <div class="at-hero h-100" id="hero">
      <div class="small fw-bold opacity-75">Latest pH reading</div>
      <div class="d-flex align-items-end gap-3 my-2">
        <div class="big" id="latestPh">{{ $stats['latest']['ph'] ?? '—' }}</div>
        <span class="pill bg-white text-dark mb-1" id="latestStatus">{{ $stats['latest']['status'] ?? 'No readings yet' }}</span>
      </div>
      <div class="small opacity-90" id="latestAt">{{ $stats['latest']['at'] ?? 'Waiting for the first upload from the ESP32' }}</div>
      <hr class="border-white opacity-25">
      <div class="d-flex justify-content-between small">
        <span><span class="dot {{ $stats['device_online'] ? 'on' : 'off' }}" id="devDot"></span><span id="devText">{{ $stats['device_online'] ? 'ESP32 online' : 'ESP32 offline' }}</span></span>
        <span>Last upload: <span id="lastUpload">{{ $stats['last_upload'] ?? 'never' }}</span></span>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="at-card h-100 card-body-pad">
      <div class="row g-3 align-items-center">
        <div class="col-auto"><div class="ring" id="ring" style="--v: {{ $stats['in_range_pct'] ?? 0 }}"><span id="ringText">{{ $stats['in_range_pct'] !== null ? $stats['in_range_pct'].'%' : '—' }}</span></div></div>
        <div class="col"><div class="fw-bold">Readings in range today</div><div class="small text-muted">Share of today’s readings inside the configured pH range ({{ $stats['ph_min'] }}–{{ $stats['ph_max'] }}).</div></div>
      </div>
      <div class="row g-2 mt-2">
        <div class="col-6 col-md-4"><div class="at-tile"><div class="v" id="sToday">{{ $stats['today_count'] }}</div><div class="l">Readings today</div></div></div>
        <div class="col-6 col-md-4"><div class="at-tile"><div class="v" id="sAlerts">{{ $stats['active_alerts'] }}</div><div class="l">Active alerts</div></div></div>
        <div class="col-12 col-md-4"><div class="at-tile"><div class="v" id="sItems">{{ $stats['total_items'] }}</div><div class="l">Inventory items</div></div></div>
      </div>
    </div>
  </div>
  @else
  <div class="col-12"><div class="at-card card-body-pad"><div class="row g-2">
    <div class="col-6 col-md-3"><div class="at-tile"><div class="v" id="sAlerts">{{ $stats['active_alerts'] }}</div><div class="l">Active alerts</div></div></div>
    <div class="col-6 col-md-3"><div class="at-tile"><div class="v" id="sItems">{{ $stats['total_items'] }}</div><div class="l">Inventory items</div></div></div>
  </div></div></div>
  @endif
</div>

<div class="row g-2 mb-3">
  <div class="col-6 col-md-3"><div class="at-tile"><div class="v text-warning" id="sLow">{{ $stats['low_stock'] }}</div><div class="l">Low-stock items</div></div></div>
  <div class="col-6 col-md-3"><div class="at-tile"><div class="v text-danger" id="sOut">{{ $stats['out_of_stock'] }}</div><div class="l">Out of stock</div></div></div>
  <div class="col-6 col-md-3"><div class="at-tile"><div class="v" id="sInsp">{{ $stats['needs_inspection'] }}</div><div class="l">Need inspection</div></div></div>
  <div class="col-6 col-md-3"><div class="at-tile"><div class="v" id="sRepl">{{ $stats['due_replacement'] }}</div><div class="l">Due for replacement</div></div></div>
</div>

<div class="row g-2 mb-3">
  @can('stock.move')<div class="col-6 col-md-3"><a class="at-qa" href="{{ route('inventory.index') }}"><i class="bi bi-arrow-left-right"></i>Record stock</a></div>@endcan
  @can('inspections.record')<div class="col-6 col-md-3"><a class="at-qa" href="{{ route('containers.index') }}"><i class="bi bi-clipboard2-check"></i>Inspect container</a></div>@endcan
  @can('inventory.manage')<div class="col-6 col-md-3"><a class="at-qa" href="{{ route('inventory.create') }}"><i class="bi bi-plus-circle"></i>Add item</a></div>@endcan
  @can('alerts.view')<div class="col-6 col-md-3"><a class="at-qa" href="{{ route('alerts.index', ['status' => 'new']) }}"><i class="bi bi-bell"></i>View alerts</a></div>@endcan
</div>

@if($can)
<div class="row g-3 mb-3">
  <div class="col-lg-8"><div class="at-card h-100"><div class="card-head"><h2>pH trend</h2><a class="small fw-bold" href="{{ route('monitoring.index') }}">Open monitor</a></div>
    <div class="card-body-pad" style="height:290px"><canvas id="trend"></canvas></div></div></div>
</div>
<div class="row g-3 mb-3">
  <div class="col-lg-4"><div class="at-card h-100"><div class="card-head"><h2>Reading quality today</h2><span class="small text-muted">{{ now()->format('M j') }}</span></div><div class="quality-chart"><canvas id="qualityMix"></canvas></div></div></div>
  <div class="col-lg-8"><div class="at-card h-100"><div class="card-head"><h2>Recent pH readings</h2><a class="small fw-bold" href="{{ route('readings.index') }}">All readings</a></div>
    <div class="table-responsive"><table class="table"><thead><tr><th>Time</th><th>pH</th><th>Status</th></tr></thead><tbody id="recentBody">
      @forelse($stats['recent'] as $r)<tr><td class="small">{{ $r['at'] }}</td><td class="fw-bold">{{ $r['ph'] }}</td><td><span class="pill pill-{{ $r['class'] }}">{{ $r['status'] }}</span></td></tr>
      @empty<tr><td colspan="3">@include('partials.empty', ['icon' => 'bi-droplet', 'title' => 'No readings yet', 'text' => 'Readings appear here once the ESP32 uploads data.'])</td></tr>@endforelse
    </tbody></table></div></div></div>
</div>
@endif

<div class="row g-3 mb-3">
  <div class="col-lg-6"><div class="at-card h-100"><div class="card-head"><h2>Recent alerts</h2><a class="small fw-bold" href="{{ route('alerts.index') }}">All</a></div>
    @forelse($alerts as $a)
      <div class="d-flex gap-2 align-items-start px-3 py-2 border-top" style="border-color:var(--at-line)!important">
        <span class="pill pill-{{ $a->severity_class }} mt-1">{{ ucfirst($a->severity) }}</span>
        <div class="flex-grow-1"><div class="fw-bold">{{ $a->title }}</div><div class="small text-muted">{{ $a->description }}</div><div class="small text-muted">@dt($a->created_at) · {{ ucfirst($a->status) }}</div></div>
      </div>
    @empty @include('partials.empty', ['icon' => 'bi-check2-circle', 'title' => 'No active alerts', 'text' => 'Everything looks fine right now.']) @endforelse
  </div></div>
  <div class="col-lg-6"><div class="at-card h-100"><div class="card-head"><h2>Low-stock inventory</h2><a class="small fw-bold" href="{{ route('inventory.index', ['stock' => 'low']) }}">View</a></div>
    @forelse($lowStock as $i)
      <a href="{{ route('inventory.show', $i) }}" class="d-flex justify-content-between align-items-center px-3 py-2 border-top text-decoration-none text-body" style="border-color:var(--at-line)!important">
        <div><div class="fw-bold">{{ $i->name }}</div><div class="small text-muted">{{ $i->category?->name ?? 'Uncategorized' }} · min @qty($i->min_stock)</div></div>
        <div class="text-end"><div class="fw-bold">@qty($i->quantity) {{ $i->unit }}</div><span class="pill pill-{{ $i->stock_class }}">{{ $i->stock_label }}</span></div>
      </a>
    @empty @include('partials.empty', ['icon' => 'bi-box-seam', 'title' => 'Stock levels are healthy']) @endforelse
  </div></div>
</div>

<div class="row g-3">
  <div class="col-lg-6"><div class="at-card h-100"><div class="card-head"><h2>Upcoming container inspections</h2><a class="small fw-bold" href="{{ route('containers.index') }}">All</a></div>
    @forelse($inspections as $c)
      <a href="{{ route('containers.show', $c) }}" class="d-flex justify-content-between align-items-center px-3 py-2 border-top text-decoration-none text-body" style="border-color:var(--at-line)!important">
        <div><div class="fw-bold">{{ $c->code }}</div><div class="small text-muted">{{ $c->type }}</div></div>
        <div class="text-end"><div class="small">@d($c->next_inspection_date)</div>@if($c->is_inspection_overdue)<span class="pill pill-bad">Overdue</span>@else<span class="pill pill-ok">Scheduled</span>@endif</div>
      </a>
    @empty @include('partials.empty', ['icon' => 'bi-bucket', 'title' => 'No scheduled inspections']) @endforelse
  </div></div>
  <div class="col-lg-6"><div class="at-card h-100"><div class="card-head"><h2>Recent activity</h2>@can('audit.view')<a class="small fw-bold" href="{{ route('audit.index') }}">Audit log</a>@endcan</div>
    @forelse($activity as $l)
      <div class="px-3 py-2 border-top" style="border-color:var(--at-line)!important"><div class="fw-bold small">{{ $l->action }} <span class="fw-normal text-muted">{{ $l->subject_label }}</span></div><div class="small text-muted">{{ $l->user?->name ?? 'System' }} · @dt($l->created_at)</div></div>
    @empty @include('partials.empty', ['icon' => 'bi-journal', 'title' => 'No activity yet']) @endforelse
  </div></div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  const stats = @json($stats);
  const set = (id, v) => { const e = document.getElementById(id); if (e) e.textContent = v; };
  let chart = null;
  const canvas = document.getElementById('trend');
  if (canvas) { chart = AQ.lineChart(canvas); AQ.setChart(chart, stats.chart.labels, stats.chart.values, stats.ph_min, stats.ph_max); }
  const mixCanvas = document.getElementById('qualityMix');
  const mixChart = mixCanvas ? new Chart(mixCanvas, { type: 'doughnut', data: { labels: stats.status_mix.labels, datasets: [{ data: stats.status_mix.values, backgroundColor: ['#2bc4b4','#d98a12','#d64545','#8b98a5'], borderWidth: 0, hoverOffset: 7 }] }, options: { responsive: true, maintainAspectRatio: false, cutout: '72%', animation: { duration: 700 }, plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 18 } } } } }) : null;

  function render(s) {
    ['active_alerts:sAlerts', 'total_items:sItems', 'low_stock:sLow', 'out_of_stock:sOut', 'needs_inspection:sInsp', 'due_replacement:sRepl'].forEach(p => { const [k, id] = p.split(':'); set(id, s[k]); });
    if (!s.can_monitor) return;
    set('latestPh', s.latest ? s.latest.ph : '—');
    set('latestStatus', s.latest ? s.latest.status : 'No readings yet');
    set('latestAt', s.latest ? s.latest.at : 'Waiting for the first upload from the ESP32');
    set('sToday', s.today_count); set('lastUpload', s.last_upload || 'never');
    set('devText', s.device_online ? 'ESP32 online' : 'ESP32 offline');
    document.getElementById('devDot').className = 'dot ' + (s.device_online ? 'on' : 'off');
    set('ringText', s.in_range_pct === null ? '—' : s.in_range_pct + '%');
    document.getElementById('ring').style.setProperty('--v', s.in_range_pct || 0);
    if (chart) AQ.setChart(chart, s.chart.labels, s.chart.values, s.ph_min, s.ph_max);
    if (mixChart) { mixChart.data.labels = s.status_mix.labels; mixChart.data.datasets[0].data = s.status_mix.values; mixChart.update(); }
    const body = document.getElementById('recentBody');
    if (body && s.recent.length) body.innerHTML = s.recent.map(r => `<tr><td class="small">${r.at}</td><td class="fw-bold">${r.ph}</td><td><span class="pill pill-${r.class}">${r.status}</span></td></tr>`).join('');
  }
  render(stats);
  AQ.poll('{{ route('dashboard.stats') }}', render, 15000);
})();
</script>
@endpush
