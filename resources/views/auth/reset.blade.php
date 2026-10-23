@extends('layouts.guest')
@section('title', 'Choose a new password')
@section('heading', 'Choose a new password')
@section('lead', 'Use at least 8 characters with letters and numbers.')
@section('content')
<form method="post" action="{{ route('password.update') }}" class="d-grid gap-3">
  @csrf
  <input type="hidden" name="token" value="{{ $token }}">
  <div><label class="form-label" for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email', $email) }}" class="form-control" required></div>
  <div><label class="form-label" for="password">New password</label><input id="password" type="password" name="password" class="form-control" autocomplete="new-password" required></div>
  <div><label class="form-label" for="password_confirmation">Confirm new password</label><input id="password_confirmation" type="password" name="password_confirmation" class="form-control" autocomplete="new-password" required></div>
  <button class="btn btn-primary btn-lg" type="submit">Update password</button>
</form>
@endsection
