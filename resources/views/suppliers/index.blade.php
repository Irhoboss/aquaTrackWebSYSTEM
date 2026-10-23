@extends('layouts.app')
@section('title', 'Suppliers')
@section('content')
<div class="at-card card-body-pad mb-3">
  <form class="row g-2 align-items-end" method="get">
    <div class="col-12 col-md-5"><label class="form-label">Search</label><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Name, contact, email or supplied items"></div>
    <div class="col-6 col-md-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="">All</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="inactive" @selected(request('status') === 'inactive')>Inactive</option></select></div>
    <div class="col-6 col-md-4 d-flex gap-2"><button class="btn btn-primary flex-grow-1">Filter</button><a class="btn btn-soft" href="{{ route('suppliers.index') }}">Reset</a><a class="btn btn-primary" href="{{ route('suppliers.create') }}"><i class="bi bi-plus-lg"></i></a></div>
  </form>
</div>
<div class="at-card"><div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Supplier</th><th>Contact</th><th>Items</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($suppliers as $s)
  <tr><td class="fw-bold">{{ $s->name }}<div class="small fw-normal text-muted">{{ $s->address }}</div></td>
    <td class="small">{{ $s->contact_person }}<div class="text-muted">{{ $s->phone }} {{ $s->email }}</div></td>
    <td>{{ $s->items_count }}</td><td><span class="pill pill-{{ $s->status === 'active' ? 'ok' : 'muted' }}">{{ ucfirst($s->status) }}</span></td>
    <td class="text-end"><a class="btn-circle" href="{{ route('suppliers.show', $s) }}" aria-label="Open {{ $s->name }}"><i class="bi bi-arrow-right"></i></a></td></tr>
@empty<tr><td colspan="5">@include('partials.empty', ['icon' => 'bi-truck', 'title' => 'No suppliers yet', 'text' => 'Add the businesses you buy supplies from.'])</td></tr>@endforelse
</tbody></table></div><div class="px-3 pt-3">{{ $suppliers->links() }}</div></div>
@endsection
