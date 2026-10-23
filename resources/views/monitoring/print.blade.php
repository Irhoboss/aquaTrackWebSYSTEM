<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>pH readings report</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>body{font-family:system-ui,sans-serif;padding:24px}@media print{.no-print{display:none}}</style></head>
<body onload="window.print()">
<h3>{{ $brand['name'] }} — pH readings report</h3>
<p class="text-muted">Generated @dt(now()) · {{ $readings->count() }} readings (max 500 shown) @if(!empty($filters['from']) || !empty($filters['to']))· Period: {{ $filters['from'] ?? '…' }} to {{ $filters['to'] ?? '…' }}@endif</p>
<table class="table table-sm table-bordered"><thead><tr><th>Measured</th><th>Device</th><th>pH</th><th>Status</th></tr></thead><tbody>
@foreach($readings as $r)<tr><td>@dt($r->measured_at)</td><td>{{ $r->device?->device_id }}</td><td>{{ $r->ph_value === null ? '—' : number_format($r->ph_value, 2) }}</td><td>{{ $r->status_label }}</td></tr>@endforeach
</tbody></table>
<p class="small text-muted">Monitoring aid only. Sensor readings alone do not certify water as potable and do not replace laboratory testing or regulatory compliance. The pH range is configurable and is not an official drinking-water standard.</p>
<button class="btn btn-primary no-print" onclick="window.print()">Print</button>
</body></html>
