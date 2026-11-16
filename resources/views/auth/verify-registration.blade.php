@extends('layouts.guest')
@section('title', 'Verify your email')
@section('heading', 'Verify your email')
@section('lead', 'Enter the six-digit code sent to your email to finish creating your account.')
@section('content')
<form method="post" action="{{ route('register.verify.submit') }}" class="d-grid gap-3">
  @csrf
  <input type="hidden" name="email" value="{{ $email }}">
  <div><label class="form-label" for="code">Verification code</label><input id="code" name="code" value="{{ old('code') }}" class="form-control" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required autofocus></div>
  <button class="btn btn-primary btn-lg" type="submit">Verify email</button>
</form>
<form method="post" action="{{ route('register.verify.resend') }}" class="d-grid mt-3">
  @csrf
  <input type="hidden" name="email" value="{{ $email }}">
  <button class="btn btn-link btn-sm" type="submit">Send a new code</button>
</form>
<a class="d-block text-center small fw-bold mt-2" href="{{ route('login') }}">Back to sign in</a>
@endsection
