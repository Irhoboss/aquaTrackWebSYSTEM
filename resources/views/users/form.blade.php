@extends('layouts.app')
@section('title', $user->exists ? 'Edit user' : 'Create user')
@section('content')
<div class="at-card card-body-pad" style="max-width:760px">
<form method="post" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" class="row g-3">
  @csrf @if($user->exists) @method('PUT') @endif
  <div class="col-md-6"><label class="form-label">Full name</label><input name="name" value="{{ old('name', $user->name) }}" class="form-control" required></div>
  <div class="col-md-6"><label class="form-label">Username</label><input name="username" value="{{ old('username', $user->username) }}" class="form-control" required></div>
  <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control" required></div>
  <div class="col-md-6"><label class="form-label">Contact number</label><input name="phone" value="{{ old('phone', $user->phone) }}" class="form-control"></div>
  <div class="col-md-6"><label class="form-label">Role</label><select name="role" class="form-select">@foreach(\App\Models\User::ROLES as $k => $v)<option value="{{ $k }}" @selected(old('role', $user->role) === $k)>{{ $v }}</option>@endforeach</select></div>
  @if($user->exists)<div class="col-md-6 d-flex align-items-end"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="act" @checked(old('is_active', $user->is_active))><label class="form-check-label" for="act">Account is active</label></div></div>
  @else<div class="col-12 small text-muted">A temporary password is generated and shown once after you save. The user must change it at first sign-in.</div>@endif
  <div class="col-12 d-flex gap-2"><button class="btn btn-primary">{{ $user->exists ? 'Save changes' : 'Create account' }}</button><a class="btn btn-soft" href="{{ route('users.index') }}">Cancel</a></div>
</form></div>
@endsection
