@extends('layouts.app')
@section('title', 'pH monitoring')
@section('subtitle', 'Live readings from the ESP32 sensor')
@section('content')
<div class="mb-3">@include('partials.disclaimer')</div>

<div class="row g-3 mb-3">
  <div class="col-lg-4">
    <div class="at-hero h-100">
      <div class="small fw-bold opacity-75">Latest pH value</div>
      <div class="big my-2" id="ph">—</div>
      <span class="pill bg-white text-dark" id="status">Waiting for data</span>
      <div class="small mt-3 opacity-90" id="at">—</div>
      <div class="small opacity-90" id="extra"></div>
      <div class="mt-4"><div class="ph-scale"><span class="mark" id="mark" style="left:50%"></span></div>
        <div class="d-flex justify-content-between small mt-2 opacity-90"><span>0</span><span>7</span><span>14</span></div></div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="at-card h-100">
      <div class="card-head"><h2>pH history</h2>
        <div class="seg" id="rangeSeg">
          <button data-r="1h">1 h</button><button data-r="24h" class="active">24 h</button><button data-r="7d">7 days</button><button data-r="30d">30 days</button>
        </div></div>
      <div class="card-body-pad"><div style="height:260px"><canvas id="chart"></canvas></div>
        <div class="row g-2 mt-2">
          <div class="col-3"><div class="at-tile"><div class="v" id="sMin">—</div><div class="l">Min</div></div></div>
          <div class="col-3"><div class="at-tile"><div class="v" id="sAvg">—</div><div class="l">Average</div></div></div>
          <div class="col-3"><div class="at-tile"><div class="v" id="sMax">—</div><div class="l">Max</div></div></div>
          <div class="col-3"><div class="at-tile"><div class="v" id="sCnt">—</div><div class="l">Readings</div></div></div>
        </div></div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-4"><div class="at-card h-100"><div class="card-head"><h2>Devices</h2>@can('settings.manage')<a class="small fw-bold" href="{{ route('settings.index') }}#devices">Manage</a>@endcan</div>
    <div class="card-body-pad" id="devices"><div class="text-muted small">Loading…</div></div></div></div>
  <div class="col-lg-8"><div class="at-card h-100"><div class="card-head"><h2>Latest readings</h2><a class="small fw-bold" href="{{ route('readings.index') }}">Full history</a></div>
    <div class="table-responsive"><table class="table"><thead><tr><th>Measured</th><th>Device</th><th>pH</th><th>Status</th></tr></thead><tbody id="recent"></tbody></table></div></div></div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  let range = '24h';
  const chart = AQ.lineChart(document.getElementById('chart'));
  const $ = id => document.getElementById(id);
  const f = v => v === null || v === undefined ? '—' : Number(v).toFixed(2);

  function render(d) {
    const l = d.latest;
    $('ph').textContent = l ? l.ph : '—';
    $('status').textContent = l ? l.status : 'Waiting for data';
    $('at').textContent = l ? 'Measured ' + l.at + ' · ' + l.device : 'No readings have been received yet.';
    $('extra').textContent = l && l.voltage ? 'Probe signal: ' + l.voltage + ' mV' : '';
    if (l && l.ph !== '—') $('mark').style.left = Math.max(0, Math.min(100, parseFloat(l.ph) / 14 * 100)) + '%';
    $('sMin').textContent = f(d.stats.min); $('sAvg').textContent = f(d.stats.avg); $('sMax').textContent = f(d.stats.max); $('sCnt').textContent = d.stats.count;
    AQ.setChart(chart, d.chart.labels, d.chart.values, d.ph_min, d.ph_max);
    $('devices').innerHTML = d.devices.length ? d.devices.map(v => `<div class="d-flex justify-content-between align-items-center py-2 border-bottom" style="border-color:var(--at-line)!important">
        <div><div class="fw-bold">${v.name}</div><div class="small text-muted">${v.device_id}</div></div>
        <div class="text-end small"><span class="dot ${v.online ? 'on' : 'off'}"></span>${v.active ? (v.online ? 'Online' : 'Offline') : 'Revoked'}<div class="text-muted">${v.last_seen}</div></div></div>`).join('') : '<div class="text-muted small">No devices registered.</div>';
    $('recent').innerHTML = d.recent.length ? d.recent.map(r => `<tr><td class="small">${r.at}</td><td class="small">${r.device}</td><td class="fw-bold">${r.ph}</td><td><span class="pill pill-${r.class}">${r.status}</span></td></tr>`).join('')
      : '<tr><td colspan="4" class="text-center text-muted py-4">No readings yet.</td></tr>';
  }
  document.querySelectorAll('#rangeSeg button').forEach(b => b.addEventListener('click', () => {
    document.querySelectorAll('#rangeSeg button').forEach(x => x.classList.remove('active')); b.classList.add('active'); range = b.dataset.r;
    fetch('{{ route('monitoring.live') }}?range=' + range, { headers: { Accept: 'application/json' } }).then(r => r.json()).then(render);
  }));
  AQ.poll(() => '{{ route('monitoring.live') }}?range=' + range, render, 10000);
})();
</script>
@endpush
