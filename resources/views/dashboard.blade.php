@extends('layouts.app')
@section('title', 'Dashboard')
@section('page', 'dashboard')
@section('search', 'Search readings')

@php
  $low = config('ph.acidic_below'); $high = config('ph.alkaline_above');
  $pLow = round($low / 14 * 100, 2); $pHigh = round($high / 14 * 100, 2);
@endphp

@section('content')
<div class="page-head">
  <div><h1>Water overview</h1><p>Live pH from the ESP32 sample probe, refreshed every 3 seconds.</p></div>
</div>

<div class="dash">
  <article class="glass hero" id="hero" data-s="">
    <div class="between"><span class="muted">Current pH</span><span class="badge" id="status">No data</span></div>
    <div class="ph-num" id="phNum">–</div>

    <div>
      <div class="scale">
        <span class="z a" style="width:{{ $pLow }}%"></span>
        <span class="z n" style="width:{{ $pHigh - $pLow }}%"></span>
        <span class="z k" style="width:{{ 100 - $pHigh }}%"></span>
        <i id="marker"></i>
      </div>
      <div class="scale-lbl">
        <span class="f">0</span><span style="left:{{ $pLow }}%">{{ $low }}</span>
        <span style="left:{{ $pHigh }}%">{{ $high }}</span><span class="l">14</span>
      </div>
    </div>

    <div class="led-row">
      <div class="led" aria-hidden="true"><i class="r"></i><i class="g"></i><i class="b"></i></div>
      <span id="ledTxt">LED off</span>
    </div>

    <dl class="meta">
      <div><dt>Raw ADC</dt><dd id="raw">–</dd></div>
      <div><dt>Voltage</dt><dd id="volt">–</dd></div>
      <div><dt>Updated</dt><dd id="upd">–</dd></div>
    </dl>

    <div class="actions">
      <button class="btn primary" id="sim" type="button"><svg class="i"><use href="#i-bolt"/></svg>Simulate reading</button>
      <a class="btn" href="{{ route('reports') }}"><svg class="i"><use href="#i-chart"/></svg>Open reports</a>
      <a class="btn" href="{{ route('chat.index') }}"><svg class="i"><use href="#i-chat"/></svg>Team chat</a>
    </div>
  </article>

  <section class="stats">
    @foreach ([['sAvg', 'Average, last 24h'], ['sLo', 'Lowest, last 24h'], ['sHi', 'Highest, last 24h'], ['sN', 'Readings, last 24h']] as [$id, $label])
      <div class="glass stat"><span>{{ $label }}</span><b id="{{ $id }}">–</b></div>
    @endforeach
  </section>

  <article class="glass card live">
    <h3>Live trend</h3>
    <div class="chart-box"><canvas id="liveChart"></canvas></div>
  </article>

  <article class="glass card recent">
    <h3>Recent readings</h3>
    <div class="rows" id="rows"></div>
    <p class="hint" id="empty" hidden>No readings yet. Power on the ESP32 or press Simulate reading.</p>
  </article>
</div>

@push('vendor')
<script defer src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
@endpush
@push('data')
<script>window.__SNAP = @json($snap);</script>
@endpush
@endsection
