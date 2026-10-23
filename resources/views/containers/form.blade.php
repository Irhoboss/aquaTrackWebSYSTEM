@extends('layouts.app')
@section('title', $container->exists ? 'Edit container' : 'Register container')
@section('content')
<div class="at-card card-body-pad" style="max-width:860px">
<form method="post" action="{{ $container->exists ? route('containers.update', $container) : route('containers.store') }}" class="row g-3">
  @csrf @if($container->exists) @method('PUT') @endif
  <div class="col-md-4"><label class="form-label">Container ID / code</label><input name="code" value="{{ old('code', $container->code) }}" class="form-control" required maxlength="50"></div>
  <div class="col-md-5"><label class="form-label">Type</label><input name="type" value="{{ old('type', $container->type) }}" class="form-control" required placeholder="Slim gallon, round gallon…"></div>
  <div class="col-md-3"><label class="form-label">Capacity (liters)</label><input type="number" step="0.01" min="0" name="capacity_liters" value="{{ old('capacity_liters', $container->capacity_liters) }}" class="form-control"></div>
  <div class="col-md-4"><label class="form-label">Date acquired</label><input type="date" name="date_acquired" value="{{ old('date_acquired', $container->date_acquired?->toDateString()) }}" class="form-control"></div>
  <div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select">@foreach(\App\Models\Container::STATUSES as $k => $v)<option value="{{ $k }}" @selected(old('status', $container->status) === $k)>{{ $v }}</option>@endforeach</select></div>
  <div class="col-md-4"><label class="form-label">Condition</label><select name="condition" class="form-select">@foreach(\App\Models\Container::CONDITIONS as $k => $v)<option value="{{ $k }}" @selected(old('condition', $container->condition) === $k)>{{ $v }}</option>@endforeach</select></div>
  <div class="col-md-4"><label class="form-label">Inspect every (days)</label><input type="number" min="1" name="inspection_frequency_days" value="{{ old('inspection_frequency_days', $container->inspection_frequency_days) }}" class="form-control" required></div>
  <div class="col-md-4"><label class="form-label">Next inspection</label><input type="date" name="next_inspection_date" value="{{ old('next_inspection_date', $container->next_inspection_date?->toDateString()) }}" class="form-control"><div class="form-text">Leave blank to schedule automatically.</div></div>
  <div class="col-md-4"><label class="form-label">Replacement due</label><input type="date" name="replacement_due_date" value="{{ old('replacement_due_date', $container->replacement_due_date?->toDateString()) }}" class="form-control"><div class="form-text">Blank = acquired date + default months.</div></div>
  <div class="col-md-6"><label class="form-label">Assigned staff</label><select name="assigned_to" class="form-select"><option value="">Unassigned</option>@foreach($users as $u)<option value="{{ $u->id }}" @selected(old('assigned_to', $container->assigned_to) == $u->id)>{{ $u->name }}</option>@endforeach</select></div>
  <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" rows="2" class="form-control">{{ old('notes', $container->notes) }}</textarea></div>
  <div class="col-12 d-flex gap-2"><button class="btn btn-primary">{{ $container->exists ? 'Save changes' : 'Register container' }}</button><a class="btn btn-soft" href="{{ $container->exists ? route('containers.show', $container) : route('containers.index') }}">Cancel</a></div>
</form></div>
@endsection
