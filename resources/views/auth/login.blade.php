@extends('layouts.guest')
@section('title', 'Sign in')
@section('heading', 'Welcome back')
@section('lead', 'Sign in to continue')
@section('content')
<form method="post" action="{{ route('login.attempt') }}" class="d-grid gap-3">
  @csrf
  <div><label class="form-label" for="login">Username or email</label>
    <input id="login" name="login" value="{{ old('login') }}" class="form-control @error('login') is-invalid @enderror" autocomplete="username" required autofocus></div>
  <div><label class="form-label" for="password">Password</label>
    <input id="password" type="password" name="password" class="form-control" autocomplete="current-password" required></div>
  <div class="d-flex justify-content-between align-items-center">
    <div class="form-check"><input class="form-check-input" type="checkbox" name="remember" id="remember"><label class="form-check-label small" for="remember">Remember me</label></div>
    <a class="small fw-bold" href="{{ route('password.request') }}">Forgot password?</a>
  </div>
  <button class="btn btn-primary btn-lg" type="submit">Sign in</button>
  @if(\App\Support\Setting::get('registration_enabled'))<p class="text-center small mb-0">New here? <a class="fw-bold" href="{{ route('register') }}">Create a buyer account</a></p>@endif
</form>
@endsection
