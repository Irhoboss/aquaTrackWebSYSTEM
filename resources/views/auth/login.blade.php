@extends('layouts.guest')
@section('title', ($authMode ?? 'login') === 'register' ? 'Register' : 'Sign in')
@section('content')
@php($mode = $authMode ?? 'login')
@php($registrationEnabled = (bool) \App\Support\Setting::get('registration_enabled'))
@if($registrationEnabled)
  <nav class="auth-mode-tabs" role="tablist" aria-label="Choose sign-in or registration">
    <a id="auth-login-tab" href="{{ route('login') }}" role="tab" data-auth-tab="login" aria-controls="auth-login-panel" aria-selected="{{ $mode === 'login' ? 'true' : 'false' }}" @if($mode === 'login') aria-current="page" @endif>Sign in</a>
    <a id="auth-register-tab" href="{{ route('register') }}" role="tab" data-auth-tab="register" aria-controls="auth-register-panel" aria-selected="{{ $mode === 'register' ? 'true' : 'false' }}" @if($mode === 'register') aria-current="page" @endif>Register</a>
  </nav>
@endif
<div class="login-form-heading" data-auth-heading="login" @if($mode !== 'login') hidden @endif>
  <h2>Welcome back</h2><p>Sign in to continue</p>
</div>
@if($registrationEnabled)
  <div class="login-form-heading" data-auth-heading="register" @if($mode !== 'register') hidden @endif>
    <h2>Register</h2><p>Verify your email to request a buyer account.</p>
  </div>
@endif
<section id="auth-login-panel" class="auth-form-panel" @if($registrationEnabled) role="tabpanel" aria-labelledby="auth-login-tab" @endif data-auth-panel="login" @if($mode !== 'login') hidden @endif>
  @include('auth.partials.login-form')
</section>
@if($registrationEnabled)
  <section id="auth-register-panel" class="auth-form-panel" role="tabpanel" aria-labelledby="auth-register-tab" data-auth-panel="register" @if($mode !== 'register') hidden @endif>
    @include('auth.partials.register-form')
  </section>
@endif
@endsection
