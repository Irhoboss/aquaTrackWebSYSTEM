@extends('layouts.app')
@section('title', 'Reading #'.$reading->id)
@section('content')
<div class="at-card card-body-pad" style="max-width:720px">
  <div class="d-flex justify-content-between align-items-start mb-3"><div><div class="text-muted small">pH value</div><div class="display-5 fw-bold">{{ $reading->ph_value === null ? '—' : number_format($reading->ph_value, 2) }}</div></div>
    <span class="pill pill-{{ $reading->status_class }}">{{ $reading->status_label }}</span></div>
  <dl class="row mb-0">
    <dt class="col-sm-4">Device</dt><dd class="col-sm-8">{{ $reading->device?->name }} ({{ $reading->device?->device_id }})</dd>
    <dt class="col-sm-4">Measured at</dt><dd class="col-sm-8">@dt($reading->measured_at)</dd>
    <dt class="col-sm-4">Received at</dt><dd class="col-sm-8">@dt($reading->received_at)</dd>
    <dt class="col-sm-4">Sensor status</dt><dd class="col-sm-8">{{ $reading->sensor_status }}</dd>
    <dt class="col-sm-4">Probe signal</dt><dd class="col-sm-8">{{ $reading->raw_voltage_mv !== null ? $reading->raw_voltage_mv.' mV' : '—' }}</dd>
    <dt class="col-sm-4">Temperature</dt><dd class="col-sm-8">{{ $reading->temperature !== null ? $reading->temperature.' °C' : 'Not measured' }}</dd>
  </dl>
  <div class="mt-3">@include('partials.disclaimer')</div>
  <a class="btn btn-soft mt-3" href="{{ route('readings.index') }}">Back to history</a>
</div>
@endsection
