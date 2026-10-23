@extends('layouts.app')
@section('title', $item->exists ? 'Edit item' : 'Add item')
@section('content')
<div class="at-card card-body-pad" style="max-width:860px">
<form method="post" action="{{ $item->exists ? route('inventory.update', $item) : route('inventory.store') }}" class="row g-3">
  @csrf @if($item->exists) @method('PUT') @endif
  <div class="col-md-4"><label class="form-label">SKU / Item ID</label><input name="sku" value="{{ old('sku', $item->sku) }}" class="form-control" required maxlength="50"></div>
  <div class="col-md-8"><label class="form-label">Item name</label><input name="name" value="{{ old('name', $item->name) }}" class="form-control" required maxlength="150"></div>
  <div class="col-md-6"><label class="form-label">Category</label><select name="category_id" class="form-select"><option value="">Uncategorized</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(old('category_id', $item->category_id) == $c->id)>{{ $c->name }}</option>@endforeach</select></div>
  <div class="col-md-6"><label class="form-label">Supplier</label><select name="supplier_id" class="form-select"><option value="">None</option>@foreach($suppliers as $s)<option value="{{ $s->id }}" @selected(old('supplier_id', $item->supplier_id) == $s->id)>{{ $s->name }}</option>@endforeach</select></div>
  <div class="col-12"><label class="form-label">Description</label><textarea name="description" rows="2" class="form-control" maxlength="1000">{{ old('description', $item->description) }}</textarea></div>
  <div class="col-6 col-md-3"><label class="form-label">Unit</label><input name="unit" value="{{ old('unit', $item->unit) }}" class="form-control" required placeholder="pcs, liters, rolls"></div>
  @unless($item->exists)<div class="col-6 col-md-3"><label class="form-label">Opening quantity</label><input type="number" step="0.01" min="0" name="quantity" value="{{ old('quantity', 0) }}" class="form-control" required></div>@endunless
  <div class="col-6 col-md-3"><label class="form-label">Minimum stock</label><input type="number" step="0.01" min="0" name="min_stock" value="{{ old('min_stock', $item->min_stock) }}" class="form-control" required></div>
  <div class="col-6 col-md-3"><label class="form-label">Maximum stock <span class="text-muted fw-normal">(optional)</span></label><input type="number" step="0.01" min="0" name="max_stock" value="{{ old('max_stock', $item->max_stock) }}" class="form-control"></div>
  <div class="col-6 col-md-3"><label class="form-label">Unit cost (PHP)</label><input type="number" step="0.01" min="0" name="unit_cost" value="{{ old('unit_cost', $item->unit_cost) }}" class="form-control"></div>
  <div class="col-md-9"><label class="form-label">Storage location</label><input name="location" value="{{ old('location', $item->location) }}" class="form-control" maxlength="150"></div>
  @if($item->exists)<div class="col-12 small text-muted">Quantity changes only through stock-in, stock-out or adjustment on the item page, so the movement history always balances.</div>@endif
  <div class="col-12 d-flex gap-2"><button class="btn btn-primary">{{ $item->exists ? 'Save changes' : 'Add item' }}</button><a class="btn btn-soft" href="{{ $item->exists ? route('inventory.show', $item) : route('inventory.index') }}">Cancel</a></div>
</form></div>
@endsection
