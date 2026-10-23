@extends('layouts.guest')
@section('title', 'Forgot password')
@section('heading', 'Reset your password')
@section('lead', 'Enter your account email and we will send a reset link.')
@section('content')
<form method="post" action="{{ route('password.email') }}" class="d-grid gap-3">
  @csrf
  <div><label class="form-label" for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control" required autofocus></div>
  <button class="btn btn-primary btn-lg" type="submit">Send reset link</button>
  <a class="text-center small fw-bold" href="{{ route('login') }}">Back to sign in</a>
</form>
@endsection
