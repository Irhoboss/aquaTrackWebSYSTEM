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
<script nonce="{{ request()->attributes->get('csp_nonce') }}">
(function () {
  let range = '24h';
  const chart = AQ.lineChart(document.getElementById('chart'));
  const $ = id => document.getElementById(id);
  const f = v => v === null || v === undefined ? '—' : Number(v).toFixed(2);
  const statusBadge = (value, tone) => {
    const badge = document.createElement('span');
    const allowed = ['ok', 'warn', 'bad', 'info', 'muted'];
    badge.className = 'pill pill-' + (allowed.includes(tone) ? tone : 'muted');
    badge.textContent = String(value ?? 'Unknown');
    return badge;
  };

  function render(d) {
    const l = d.latest;
    $('ph').textContent = l ? l.ph : '—';
    $('status').textContent = l ? l.status : 'Waiting for data';
    $('at').textContent = l ? 'Measured ' + l.at + ' · ' + l.device : 'No readings have been received yet.';
    $('extra').textContent = l && l.voltage ? 'Probe signal: ' + l.voltage + ' mV' : '';
    if (l && l.ph !== '—') $('mark').style.left = Math.max(0, Math.min(100, parseFloat(l.ph) / 14 * 100)) + '%';
    $('sMin').textContent = f(d.stats.min); $('sAvg').textContent = f(d.stats.avg); $('sMax').textContent = f(d.stats.max); $('sCnt').textContent = d.stats.count;
    AQ.setChart(chart, d.chart.labels, d.chart.values, d.ph_min, d.ph_max);
    const deviceList = $('devices');
    if (d.devices.length) {
      deviceList.replaceChildren(...d.devices.map(v => {
        const row = document.createElement('div');
        row.className = 'd-flex justify-content-between align-items-center py-2 border-bottom';
        row.style.borderColor = 'var(--at-line)';
        const info = document.createElement('div');
        const name = document.createElement('div');
        name.className = 'fw-bold';
        name.textContent = String(v.name ?? 'Unnamed device');
        const id = document.createElement('div');
        id.className = 'small text-muted';
        id.textContent = String(v.device_id ?? '');
        info.append(name, id);
        const status = document.createElement('div');
        status.className = 'text-end small';
        const dot = document.createElement('span');
        dot.className = 'dot ' + (v.online ? 'on' : 'off');
        const state = document.createElement('span');
        state.textContent = v.active ? (v.online ? 'Online' : 'Offline') : 'Revoked';
        const lastSeen = document.createElement('div');
        lastSeen.className = 'text-muted';
        lastSeen.textContent = String(v.last_seen ?? 'Never');
        status.append(dot, state, lastSeen);
        row.append(info, status);
        return row;
      }));
    } else {
      const empty = document.createElement('div');
      empty.className = 'text-muted small';
      empty.textContent = 'No devices registered.';
      deviceList.replaceChildren(empty);
    }

    const recent = $('recent');
    if (d.recent.length) {
      recent.replaceChildren(...d.recent.map(r => {
        const row = document.createElement('tr');
        for (const [value, className] of [[r.at, 'small'], [r.device, 'small'], [r.ph, 'fw-bold']]) {
          const cell = document.createElement('td');
          cell.className = className;
          cell.textContent = String(value ?? '');
          row.append(cell);
        }
        const statusCell = document.createElement('td');
        statusCell.append(statusBadge(r.status, r.class));
        row.append(statusCell);
        return row;
      }));
    } else {
      const row = document.createElement('tr');
      const cell = document.createElement('td');
      cell.colSpan = 4;
      cell.className = 'text-center text-muted py-4';
      cell.textContent = 'No readings yet.';
      row.append(cell);
      recent.replaceChildren(row);
    }
  }
  document.querySelectorAll('#rangeSeg button').forEach(b => b.addEventListener('click', () => {
    document.querySelectorAll('#rangeSeg button').forEach(x => x.classList.remove('active')); b.classList.add('active'); range = b.dataset.r;
    fetch('{{ route('monitoring.live') }}?range=' + range, { headers: { Accept: 'application/json' } }).then(r => r.json()).then(render);
  }));
  AQ.poll(() => '{{ route('monitoring.live') }}?range=' + range, render, 10000);
})();
</script>
@endpush
