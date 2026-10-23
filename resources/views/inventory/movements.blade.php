@extends('layouts.app')
@section('title', 'Stock movements')
@section('subtitle', 'Complete ledger of stock changes')
@section('content')
<div class="at-card card-body-pad mb-3">
  <form class="row g-2 align-items-end" method="get">
    <div class="col-12 col-md-3"><label class="form-label">Search</label><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Item, SKU or reason"></div>
    <div class="col-6 col-md-2"><label class="form-label">Type</label><select name="type" class="form-select"><option value="">All</option>@foreach(['in' => 'Stock in', 'out' => 'Stock out', 'adjust' => 'Adjustment'] as $k => $v)<option value="{{ $k }}" @selected(request('type') === $k)>{{ $v }}</option>@endforeach</select></div>
    <div class="col-6 col-md-2"><label class="form-label">From</label><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
    <div class="col-6 col-md-2"><label class="form-label">To</label><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
    <div class="col-6 col-md-3 d-flex gap-2"><button class="btn btn-primary flex-grow-1">Filter</button><a class="btn btn-soft" href="{{ route('inventory.movements') }}">Reset</a><a class="btn btn-soft" href="{{ route('inventory.movements', array_merge(request()->query(), ['export' => 1])) }}" aria-label="Download CSV"><i class="bi bi-download"></i></a></div>
  </form>
</div>
<div class="at-card">
  <div class="table-responsive"><table class="table table-hover"><thead><tr><th>Date</th><th>Item</th><th>Type</th><th class="text-end">Change</th><th class="text-end">Before → After</th><th>Reason</th><th>By</th></tr></thead><tbody>
  @forelse($movements as $m)
    <tr><td class="small">@dt($m->created_at)</td><td><a href="{{ route('inventory.show', $m->item) }}" class="fw-bold text-decoration-none">{{ $m->item?->name }}</a><div class="small text-muted">{{ $m->item?->sku }}</div></td>
      <td><span class="pill pill-{{ ['in' => 'ok', 'out' => 'warn', 'adjust' => 'info'][$m->type] }}">{{ ['in' => 'Stock in', 'out' => 'Stock out', 'adjust' => 'Adjustment'][$m->type] }}</span></td>
      <td class="text-end fw-bold {{ $m->quantity_change < 0 ? 'text-danger' : 'text-success' }}">{{ $m->quantity_change > 0 ? '+' : '' }}@qty($m->quantity_change)</td>
      <td class="text-end small">@qty($m->quantity_before) → @qty($m->quantity_after)</td><td class="small">{{ $m->reason }}</td><td class="small">{{ $m->user?->name ?? '—' }}</td></tr>
  @empty<tr><td colspan="7">@include('partials.empty', ['icon' => 'bi-arrow-left-right', 'title' => 'No movements found'])</td></tr>@endforelse
  </tbody></table></div>
  <div class="px-3 pt-3">{{ $movements->links() }}</div>
</div>
@endsection
