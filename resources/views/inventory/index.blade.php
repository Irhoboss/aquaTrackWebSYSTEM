@extends('layouts.app')
@section('title', 'Inventory')
@section('subtitle', 'Supplies, containers and materials')
@section('content')
<div class="at-card card-body-pad mb-3">
  <form class="row g-2 align-items-end" method="get">
    <div class="col-12 col-md-3"><label class="form-label">Search</label><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Name, SKU or location"></div>
    <div class="col-6 col-md-2"><label class="form-label">Category</label><select name="category" class="form-select"><option value="">All</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(request('category') == $c->id)>{{ $c->name }} ({{ $c->items_count }})</option>@endforeach</select></div>
    <div class="col-6 col-md-2"><label class="form-label">Stock level</label><select name="stock" class="form-select"><option value="">All</option>@foreach(['ok' => 'In stock', 'low' => 'Low stock', 'out' => 'Out of stock'] as $k => $v)<option value="{{ $k }}" @selected(request('stock') === $k)>{{ $v }}</option>@endforeach</select></div>
    <div class="col-6 col-md-2"><label class="form-label">Record</label><select name="status" class="form-select">@foreach(['active' => 'Active', 'archived' => 'Archived', 'all' => 'All'] as $k => $v)<option value="{{ $k }}" @selected(request('status', 'active') === $k)>{{ $v }}</option>@endforeach</select></div>
    <div class="col-6 col-md-3 d-flex gap-2"><button class="btn btn-primary flex-grow-1"><i class="bi bi-funnel me-1"></i>Filter</button><a class="btn btn-soft" href="{{ route('inventory.index') }}">Reset</a></div>
  </form>
</div>

<div class="d-flex flex-wrap gap-2 mb-3">
  @can('inventory.manage')<a class="btn btn-primary" href="{{ route('inventory.create') }}"><i class="bi bi-plus-lg me-1"></i>Add item</a>@endcan
  <a class="btn btn-soft" href="{{ route('inventory.movements') }}"><i class="bi bi-arrow-left-right me-1"></i>Stock movements</a>
  <a class="btn btn-soft" href="{{ route('inventory.export', request()->query()) }}"><i class="bi bi-download me-1"></i>CSV</a>
  <a class="btn btn-soft" target="_blank" href="{{ route('reports.index', ['type' => 'inventory_balances', 'print' => 1]) }}"><i class="bi bi-printer me-1"></i>Print report</a>
  @can('inventory.manage')<button class="btn btn-soft ms-md-auto" data-bs-toggle="modal" data-bs-target="#catModal"><i class="bi bi-tags me-1"></i>Categories</button>@endcan
</div>

<div class="at-card">
  <div class="table-responsive"><table class="table table-hover align-middle">
    <thead><tr><th>@include('partials.sort', ['col' => 'sku', 'label' => 'SKU'])</th><th>@include('partials.sort', ['col' => 'name', 'label' => 'Item'])</th><th>Category</th><th class="text-end">@include('partials.sort', ['col' => 'quantity', 'label' => 'On hand'])</th><th>Minimum</th><th>Status</th><th>Supplier</th><th></th></tr></thead>
    <tbody>
    @forelse($items as $i)
      <tr class="{{ $i->stock_state === 'out' ? 'row-bad' : ($i->stock_state === 'low' ? 'row-warn' : '') }}">
        <td class="small text-muted">{{ $i->sku }}</td>
        <td class="fw-bold">{{ $i->name }} @if($i->status === 'archived')<span class="pill pill-muted">Archived</span>@endif<div class="small fw-normal text-muted">{{ $i->location }}</div></td>
        <td>{{ $i->category?->name ?? '—' }}</td>
        <td class="text-end fw-bold">@qty($i->quantity) <span class="fw-normal text-muted small">{{ $i->unit }}</span></td>
        <td>@qty($i->min_stock)</td>
        <td><span class="pill pill-{{ $i->stock_class }}">{{ $i->stock_label }}</span></td>
        <td class="small">{{ $i->supplier?->name ?? '—' }}</td>
        <td class="text-end"><a class="btn-circle" href="{{ route('inventory.show', $i) }}" aria-label="Open {{ $i->name }}"><i class="bi bi-arrow-right"></i></a></td>
      </tr>
    @empty
      <tr><td colspan="8">@include('partials.empty', ['icon' => 'bi-box-seam', 'title' => 'No items found', 'text' => 'Add your first item or change the filters.'])</td></tr>
    @endforelse
    </tbody></table></div>
  <div class="px-3 pt-3">{{ $items->links() }}</div>
</div>

@can('inventory.manage')
<div class="modal fade" id="catModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content at-card">
  <div class="modal-header border-0"><h5 class="modal-title">Categories</h5><button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
  <div class="modal-body">
    <form method="post" action="{{ route('categories.store') }}" class="d-flex gap-2 mb-3">@csrf<input name="name" class="form-control" placeholder="New category name" required maxlength="80"><button class="btn btn-primary">Add</button></form>
    @foreach($categories as $c)
      <div class="d-flex justify-content-between align-items-center py-2 border-bottom"><span>{{ $c->name }} <span class="text-muted small">({{ $c->items_count }})</span></span>
        <form method="post" action="{{ route('categories.destroy', $c) }}" data-confirm="Delete category “{{ $c->name }}”?">@csrf @method('DELETE')<button class="btn btn-sm text-danger" aria-label="Delete category"><i class="bi bi-trash"></i></button></form></div>
    @endforeach
  </div></div></div></div>
@endcan
@endsection
