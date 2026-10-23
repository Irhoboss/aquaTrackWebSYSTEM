@extends('layouts.app')
@section('title', $item->name)
@section('subtitle', $item->sku)
@section('content')
<div class="row g-3">
  <div class="col-lg-5 vstack gap-3">
    <div class="at-card card-body-pad">
      <div class="d-flex justify-content-between align-items-start">
        <div><div class="text-muted small">On hand</div><div class="display-6 fw-bold">@qty($item->quantity) <span class="fs-6 text-muted fw-semibold">{{ $item->unit }}</span></div></div>
        <span class="pill pill-{{ $item->stock_class }}">{{ $item->stock_label }}</span>
      </div>
      @if($item->status === 'archived')<div class="pill pill-muted mt-2">Archived</div>@endif
      <dl class="row small mt-3 mb-0">
        <dt class="col-5">Category</dt><dd class="col-7">{{ $item->category?->name ?? '—' }}</dd>
        <dt class="col-5">Minimum / Maximum</dt><dd class="col-7">@qty($item->min_stock) / {{ $item->max_stock !== null ? \App\Support\Fmt::qty($item->max_stock) : '—' }}</dd>
        <dt class="col-5">Supplier</dt><dd class="col-7">{{ $item->supplier?->name ?? '—' }}</dd>
        <dt class="col-5">Unit cost</dt><dd class="col-7">{{ $item->unit_cost !== null ? 'PHP '.number_format($item->unit_cost, 2) : '—' }}</dd>
        <dt class="col-5">Location</dt><dd class="col-7">{{ $item->location ?? '—' }}</dd>
        <dt class="col-5">Date added</dt><dd class="col-7">@dt($item->created_at)</dd>
        <dt class="col-5">Last updated</dt><dd class="col-7">@dt($item->updated_at)</dd>
        <dt class="col-12 mt-2">Description</dt><dd class="col-12 mb-0">{{ $item->description ?: '—' }}</dd>
      </dl>
      @can('inventory.manage')
      <div class="d-flex flex-wrap gap-2 mt-3">
        <a class="btn btn-soft btn-sm" href="{{ route('inventory.edit', $item) }}"><i class="bi bi-pencil me-1"></i>Edit</a>
        <form method="post" action="{{ route('inventory.archive', $item) }}" data-confirm="{{ $item->status === 'active' ? 'Archive this item?' : 'Restore this item?' }}">@csrf<button class="btn btn-soft btn-sm">{{ $item->status === 'active' ? 'Archive' : 'Restore' }}</button></form>
        <form method="post" action="{{ route('inventory.destroy', $item) }}" data-confirm="Delete this item permanently? This is only possible when it has no stock history.">@csrf @method('DELETE')<button class="btn btn-sm text-danger">Delete</button></form>
      </div>
      @endcan
    </div>

    @can('stock.move')
    @if($item->status === 'active')
    <div class="at-card card-body-pad">
      <h2 class="h6 mb-3">Record stock movement</h2>
      <form method="post" action="{{ route('inventory.stock', $item) }}" class="row g-2" id="stockForm">
        @csrf
        <div class="col-12"><div class="seg w-100">
          @foreach(['in' => 'Stock in', 'out' => 'Stock out', 'adjust' => 'Adjust'] as $k => $v)
            <label class="flex-fill text-center"><input type="radio" name="type" value="{{ $k }}" class="d-none" @checked(old('type', 'in') === $k)><span class="d-block seg-opt">{{ $v }}</span></label>
          @endforeach
        </div></div>
        <div class="col-12"><label class="form-label" id="qtyLabel">Quantity ({{ $item->unit }})</label><input type="number" step="0.01" min="0" name="quantity" value="{{ old('quantity') }}" class="form-control" required></div>
        <div class="col-12"><label class="form-label">Reason</label><input name="reason" value="{{ old('reason') }}" class="form-control" required maxlength="150" placeholder="e.g. Delivery from supplier, Used for refilling, Recount"></div>
        <div class="col-md-6 only-in"><label class="form-label">Supplier</label><select name="supplier_id" class="form-select"><option value="">None</option>@foreach($suppliers as $s)<option value="{{ $s->id }}" @selected(old('supplier_id', $item->supplier_id) == $s->id)>{{ $s->name }}</option>@endforeach</select></div>
        <div class="col-md-6 only-in"><label class="form-label">Unit cost</label><input type="number" step="0.01" min="0" name="unit_cost" value="{{ old('unit_cost') }}" class="form-control"></div>
        <div class="col-12"><label class="form-label">Notes <span class="text-muted fw-normal">(optional)</span></label><input name="notes" value="{{ old('notes') }}" class="form-control" maxlength="500"></div>
        @can('stock.negative')<div class="col-12 form-check ms-2"><input class="form-check-input" type="checkbox" name="allow_negative" id="neg" value="1"><label class="form-check-label small" for="neg">Allow negative stock (owner exception)</label></div>@endcan
        <div class="col-12"><button class="btn btn-primary w-100">Save movement</button></div>
      </form>
    </div>
    @endif
    @endcan
  </div>

  <div class="col-lg-7">
    <div class="at-card"><div class="card-head"><h2>Movement history</h2></div>
      <div class="table-responsive"><table class="table"><thead><tr><th>Date</th><th>Type</th><th class="text-end">Change</th><th class="text-end">After</th><th>Reason</th><th>By</th></tr></thead><tbody>
      @forelse($movements as $m)
        <tr><td class="small">@dt($m->created_at)</td>
          <td><span class="pill pill-{{ ['in' => 'ok', 'out' => 'warn', 'adjust' => 'info'][$m->type] }}">{{ ['in' => 'Stock in', 'out' => 'Stock out', 'adjust' => 'Adjustment'][$m->type] }}</span></td>
          <td class="text-end fw-bold {{ $m->quantity_change < 0 ? 'text-danger' : 'text-success' }}">{{ $m->quantity_change > 0 ? '+' : '' }}@qty($m->quantity_change)</td>
          <td class="text-end">@qty($m->quantity_after)</td><td class="small">{{ $m->reason }}@if($m->notes)<div class="text-muted">{{ $m->notes }}</div>@endif</td><td class="small">{{ $m->user?->name ?? '—' }}</td></tr>
      @empty<tr><td colspan="6">@include('partials.empty', ['icon' => 'bi-arrow-left-right', 'title' => 'No movements yet'])</td></tr>@endforelse
      </tbody></table></div>
      <div class="p-3">{{ $movements->links() }}</div>
    </div>
  </div>
</div>
@endsection

@push('head')<style>.seg label{cursor:pointer}.seg-opt{padding:.4rem .6rem;border-radius:999px;font-size:.82rem;font-weight:700;color:var(--at-muted)}.seg input:checked + .seg-opt{background:linear-gradient(135deg,#3ccbbb,var(--at-teal-dark));color:#fff}</style>@endpush
@push('scripts')
<script>
(function () {
  const form = document.getElementById('stockForm'); if (!form) return;
  const label = document.getElementById('qtyLabel'); const unit = @json($item->unit);
  function sync() {
    const t = form.querySelector('input[name=type]:checked').value;
    label.textContent = t === 'adjust' ? 'Counted quantity now on hand (' + unit + ')' : 'Quantity (' + unit + ')';
    form.querySelectorAll('.only-in').forEach(e => e.style.display = t === 'in' ? '' : 'none');
  }
  form.querySelectorAll('input[name=type]').forEach(r => r.addEventListener('change', sync)); sync();
})();
</script>
@endpush
