@extends('layouts.app')
@section('title', 'Reports')
@section('page', 'reports')

@section('content')
<div class="page-head">
  <div><h1>Reports</h1><p>Trends and water-quality distribution from stored readings.</p></div>
  <div class="head-tools">
    <div class="seg" role="group" aria-label="Time range">
      <button type="button" class="on" data-range="24h">24 hours</button>
      <button type="button" data-range="7d">7 days</button>
      <button type="button" data-range="30d">30 days</button>
    </div>
    <a class="btn" id="export" href="{{ route('reports.export', ['range' => '24h']) }}"><svg class="i"><use href="#i-down"/></svg>Export CSV</a>
  </div>
</div>

<section class="stats">
  @foreach ([['rAvg', 'Average pH'], ['rLo', 'Lowest pH'], ['rHi', 'Highest pH'], ['rNormal', 'Time in normal range'], ['rN', 'Readings']] as [$id, $label])
    <div class="glass stat"><span>{{ $label }}</span><b id="{{ $id }}">–</b></div>
  @endforeach
</section>

<p class="hint glass card" id="empty" hidden>No readings in this range yet.</p>

<div class="rep">
  <article class="glass card wide"><h3>pH trend</h3><div class="chart-box tall"><canvas id="cLine"></canvas></div></article>
  <article class="glass card"><h3>Water status split</h3><div class="chart-box"><canvas id="cPie"></canvas></div></article>
  <article class="glass card"><h3>Out-of-range readings</h3><div class="chart-box"><canvas id="cBar"></canvas></div></article>
</div>

@push('vendor')
<script defer src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
@endpush
@endsection
