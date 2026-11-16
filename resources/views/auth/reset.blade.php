@extends('layouts.guest')
@section('title', 'Verify and reset password')
@section('heading', 'Verify your email')
@section('lead', 'Enter the six-digit code sent to your email, then choose a new password.')
@section('content')
<form method="post" action="{{ route('password.otp.verify') }}" class="d-grid gap-3">
  @csrf
  <input type="hidden" name="email" value="{{ $email }}">
  <div><label class="form-label" for="code">Verification code</label><input id="code" name="code" value="{{ old('code') }}" class="form-control" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required autofocus></div>
  <div><label class="form-label" for="password">New password</label><input id="password" type="password" name="password" class="form-control" autocomplete="new-password" required></div>
  <div><label class="form-label" for="password_confirmation">Confirm new password</label><input id="password_confirmation" type="password" name="password_confirmation" class="form-control" autocomplete="new-password" required></div>
  <button class="btn btn-primary btn-lg" type="submit">Update password</button>
</form>
<form method="post" action="{{ route('password.email') }}" class="d-grid mt-3">
  @csrf
  <input type="hidden" name="email" value="{{ $email }}">
  <button class="btn btn-link btn-sm" type="submit">Send a new code</button>
</form>
<a class="d-block text-center small fw-bold mt-2" href="{{ route('login') }}">Back to sign in</a>
@endsection
