@extends('layouts.app')
@section('title', $supplier->exists ? 'Edit supplier' : 'Add supplier')
@section('content')
<div class="at-card card-body-pad" style="max-width:800px">
<form method="post" action="{{ $supplier->exists ? route('suppliers.update', $supplier) : route('suppliers.store') }}" class="row g-3">
  @csrf @if($supplier->exists) @method('PUT') @endif
  <div class="col-md-8"><label class="form-label">Supplier name</label><input name="name" value="{{ old('name', $supplier->name) }}" class="form-control" required maxlength="150"></div>
  <div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select"><option value="active" @selected(old('status', $supplier->status) === 'active')>Active</option><option value="inactive" @selected(old('status', $supplier->status) === 'inactive')>Inactive</option></select></div>
  <div class="col-md-4"><label class="form-label">Contact person</label><input name="contact_person" value="{{ old('contact_person', $supplier->contact_person) }}" class="form-control"></div>
  <div class="col-md-4"><label class="form-label">Phone</label><input name="phone" value="{{ old('phone', $supplier->phone) }}" class="form-control" placeholder="09XX XXX XXXX"></div>
  <div class="col-md-4"><label class="form-label">Email</label><input type="email" name="email" value="{{ old('email', $supplier->email) }}" class="form-control"></div>
  <div class="col-12"><label class="form-label">Address</label><input name="address" value="{{ old('address', $supplier->address) }}" class="form-control"></div>
  <div class="col-12"><label class="form-label">Supplied items</label><textarea name="supplied_items" rows="2" class="form-control">{{ old('supplied_items', $supplier->supplied_items) }}</textarea></div>
  <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" rows="2" class="form-control">{{ old('notes', $supplier->notes) }}</textarea></div>
  <div class="col-12 d-flex gap-2"><button class="btn btn-primary">{{ $supplier->exists ? 'Save changes' : 'Add supplier' }}</button><a class="btn btn-soft" href="{{ route('suppliers.index') }}">Cancel</a></div>
</form></div>
@endsection
