@extends('layouts.app')
@section('title', $supplier->name)
@section('content')
<div class="row g-3">
  <div class="col-lg-4"><div class="at-card card-body-pad">
    <span class="pill pill-{{ $supplier->status === 'active' ? 'ok' : 'muted' }}">{{ ucfirst($supplier->status) }}</span>
    <dl class="small mt-3 mb-0"><dt>Contact person</dt><dd>{{ $supplier->contact_person ?: '—' }}</dd><dt>Phone</dt><dd>{{ $supplier->phone ?: '—' }}</dd><dt>Email</dt><dd>{{ $supplier->email ?: '—' }}</dd><dt>Address</dt><dd>{{ $supplier->address ?: '—' }}</dd><dt>Supplied items</dt><dd>{{ $supplier->supplied_items ?: '—' }}</dd><dt>Notes</dt><dd class="mb-0">{{ $supplier->notes ?: '—' }}</dd></dl>
    <div class="d-flex gap-2 mt-3"><a class="btn btn-soft btn-sm" href="{{ route('suppliers.edit', $supplier) }}">Edit</a>
      <form method="post" action="{{ route('suppliers.destroy', $supplier) }}" data-confirm="Delete this supplier?">@csrf @method('DELETE')<button class="btn btn-sm text-danger">Delete</button></form></div>
  </div></div>
  <div class="col-lg-8 vstack gap-3">
    <div class="at-card"><div class="card-head"><h2>Items from this supplier</h2></div>
      @forelse($items as $i)<a href="{{ route('inventory.show', $i) }}" class="d-flex justify-content-between px-3 py-2 border-top text-decoration-none text-body" style="border-color:var(--at-line)!important"><span class="fw-bold">{{ $i->name }}</span><span>@qty($i->quantity) {{ $i->unit }} <span class="pill pill-{{ $i->stock_class }}">{{ $i->stock_label }}</span></span></a>
      @empty @include('partials.empty', ['icon' => 'bi-box-seam', 'title' => 'No items linked']) @endforelse</div>
    <div class="at-card"><div class="card-head"><h2>Related stock history</h2></div>
      <div class="table-responsive"><table class="table"><thead><tr><th>Date</th><th>Item</th><th class="text-end">Change</th><th>Reason</th></tr></thead><tbody>
      @forelse($movements as $m)<tr><td class="small">@dt($m->created_at)</td><td>{{ $m->item?->name }}</td><td class="text-end fw-bold">{{ $m->quantity_change > 0 ? '+' : '' }}@qty($m->quantity_change)</td><td class="small">{{ $m->reason }}</td></tr>
      @empty<tr><td colspan="4" class="text-center text-muted py-3">No stock movements recorded for this supplier.</td></tr>@endforelse</tbody></table></div>
      <div class="p-3">{{ $movements->links() }}</div></div>
  </div>
</div>
@endsection
