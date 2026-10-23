@extends('layouts.app')
@section('title', 'Settings')
@section('subtitle', 'Station, pH rules, devices and defaults')
@section('content')
<form method="post" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="row g-3">
  @csrf @method('PUT')
  <div class="col-lg-6"><div class="at-card card-body-pad h-100"><h2 class="h6 mb-3">Station</h2><div class="row g-3">
    <div class="col-12"><label class="form-label">Station name</label><input name="station_name" value="{{ old('station_name', $s['station_name']) }}" class="form-control" required></div>
    <div class="col-12"><label class="form-label">Address</label><input name="station_address" value="{{ old('station_address', $s['station_address']) }}" class="form-control"></div>
    <div class="col-12"><label class="form-label">Contact</label><input name="station_contact" value="{{ old('station_contact', $s['station_contact']) }}" class="form-control"></div>
    <div class="col-md-6"><label class="form-label">Logo</label><input type="file" name="logo" accept="image/*" class="form-control"></div>
    <div class="col-md-6"><label class="form-label">Favicon</label><input type="file" name="favicon" accept="image/*" class="form-control"></div>
    <div class="col-12 small text-muted">Times are stored in UTC and displayed in Philippine time ({{ config('aquatrack.display_tz') }}).</div>
  </div></div></div>

  <div class="col-lg-6"><div class="at-card card-body-pad h-100"><h2 class="h6 mb-3">pH rules and sensor</h2><div class="row g-3">
    <div class="col-6"><label class="form-label">Minimum pH</label><input type="number" step="0.01" name="ph_min" value="{{ old('ph_min', $s['ph_min']) }}" class="form-control" required></div>
    <div class="col-6"><label class="form-label">Maximum pH</label><input type="number" step="0.01" name="ph_max" value="{{ old('ph_max', $s['ph_max']) }}" class="form-control" required></div>
    <div class="col-12"><div class="notice">These limits are configurable starting values, <strong>not</strong> official drinking-water standards. Confirm the limits that apply to your station with the relevant regulator.</div></div>
    <div class="col-6"><label class="form-label">Reporting interval (seconds)</label><input type="number" name="reporting_interval_seconds" value="{{ old('reporting_interval_seconds', $s['reporting_interval_seconds']) }}" class="form-control" required><div class="form-text">Guidance sent to the ESP32 via the heartbeat.</div></div>
    <div class="col-6"><label class="form-label">Offline after (minutes)</label><input type="number" name="offline_minutes" value="{{ old('offline_minutes', $s['offline_minutes']) }}" class="form-control" required><div class="form-text">No upload for this long = “not reporting”.</div></div>
  </div></div></div>

  <div class="col-lg-6"><div class="at-card card-body-pad h-100"><h2 class="h6 mb-3">Inventory and containers</h2><div class="row g-3">
    <div class="col-md-4"><label class="form-label">Default minimum stock</label><input type="number" step="0.01" name="default_min_stock" value="{{ old('default_min_stock', $s['default_min_stock']) }}" class="form-control" required></div>
    <div class="col-md-4"><label class="form-label">Inspect every (days)</label><input type="number" name="default_inspection_days" value="{{ old('default_inspection_days', $s['default_inspection_days']) }}" class="form-control" required></div>
    <div class="col-md-4"><label class="form-label">Replace after (months)</label><input type="number" name="default_replacement_months" value="{{ old('default_replacement_months', $s['default_replacement_months']) }}" class="form-control" required></div>
  </div></div></div>

  <div class="col-lg-6"><div class="at-card card-body-pad h-100"><h2 class="h6 mb-3">Accounts and notifications</h2><div class="row g-3">
    <div class="col-md-6"><label class="form-label">Minimum password length</label><input type="number" name="password_min_length" value="{{ old('password_min_length', $s['password_min_length']) }}" class="form-control" required></div>
    <div class="col-md-6 d-flex align-items-end"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="registration_enabled" value="1" id="reg" @checked($s['registration_enabled'])><label class="form-check-label" for="reg">Allow account requests</label></div></div>
    @foreach(['notify_ph_alerts' => 'pH and sensor alerts', 'notify_inventory_alerts' => 'Inventory alerts', 'notify_container_alerts' => 'Container alerts'] as $k => $label)
      <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="{{ $k }}" value="1" id="{{ $k }}" @checked($s[$k])><label class="form-check-label" for="{{ $k }}">Send in-app notifications: {{ $label }}</label></div></div>
    @endforeach
  </div></div></div>
  <div class="col-12"><button class="btn btn-primary">Save settings</button></div>
</form>

<div class="at-card mt-4" id="devices">
  <div class="card-head"><h2>ESP32 devices</h2></div>
  <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Device</th><th>Status</th><th>Last seen</th><th>Firmware</th><th class="text-end">Actions</th></tr></thead><tbody>
  @forelse($devices as $d)
    <tr><td class="fw-bold">{{ $d->name }}<div class="small fw-normal text-muted">{{ $d->device_id }} {{ $d->location ? '· '.$d->location : '' }}</div></td>
      <td>@if(!$d->is_active)<span class="pill pill-bad">Revoked</span>@elseif($d->isOnline())<span class="pill pill-ok">Online</span>@else<span class="pill pill-warn">Offline</span>@endif</td>
      <td class="small">{{ $d->last_seen_at ? \App\Support\Fmt::dt($d->last_seen_at) : 'Never' }}</td><td class="small">{{ $d->firmware ?? '—' }}</td>
      <td class="text-end"><div class="d-inline-flex gap-1">
        <form method="post" action="{{ route('devices.regenerate', $d) }}" data-confirm="Issue a new token? The current token stops working immediately.">@csrf<button class="btn btn-soft btn-sm">New token</button></form>
        <form method="post" action="{{ route('devices.toggle', $d) }}" data-confirm="{{ $d->is_active ? 'Revoke this device?' : 'Re-enable this device?' }}">@csrf<button class="btn btn-soft btn-sm">{{ $d->is_active ? 'Revoke' : 'Enable' }}</button></form></div></td></tr>
  @empty<tr><td colspan="5">@include('partials.empty', ['icon' => 'bi-cpu', 'title' => 'No devices yet'])</td></tr>@endforelse
  </tbody></table></div>
  <div class="card-body-pad border-top" style="border-color:var(--at-line)!important">
    <form method="post" action="{{ route('devices.store') }}" class="row g-2 align-items-end">@csrf
      <div class="col-md-4"><label class="form-label">Device ID</label><input name="device_id" class="form-control" placeholder="AQUATRACK-ESP32-002" required></div>
      <div class="col-md-4"><label class="form-label">Name</label><input name="name" class="form-control" required></div>
      <div class="col-md-3"><label class="form-label">Location</label><input name="location" class="form-control"></div>
      <div class="col-md-1"><button class="btn btn-primary w-100" aria-label="Register device"><i class="bi bi-plus-lg"></i></button></div>
    </form>
  </div>
</div>
@endsection
