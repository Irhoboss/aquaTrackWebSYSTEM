<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>{{ $report['title'] }}</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>body{font-family:system-ui,sans-serif;padding:24px}@media print{.no-print{display:none}}</style></head>
<body onload="window.print()">
<h3>{{ $brand['name'] }}</h3><h5>{{ $report['title'] }}</h5>
<p class="text-muted small">Generated @dt(now()) @if(!str_contains($type, 'overdue') && !str_contains($type, 'balances') && $type !== 'low_stock')· Period: {{ $from }} to {{ $to }}@endif</p>
@if($report['summary'])<p>@foreach($report['summary'] as $l => $v)<strong>{{ $l }}:</strong> {{ $v }} &nbsp; @endforeach</p>@endif
<table class="table table-sm table-bordered"><thead><tr>@foreach($report['columns'] as $c)<th>{{ $c }}</th>@endforeach</tr></thead><tbody>
@forelse($report['rows'] as $row)<tr>@foreach($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>@empty<tr><td colspan="{{ max(1, count($report['columns'])) }}" class="text-center">No data.</td></tr>@endforelse
</tbody></table>
<p class="small text-muted">Monitoring aid only. Sensor readings alone do not certify water as potable.</p>
<button class="btn btn-primary no-print" onclick="window.print()">Print</button></body></html>
