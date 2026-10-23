@extends('layouts.guest')
@section('title', 'Create buyer account')
@section('heading', 'Create your buyer account')
@section('lead', 'Sign up to browse products and stay connected with your water station.')
@section('content')
<form method="post" action="{{ route('register.store') }}" class="d-grid gap-3">
  @csrf
  <div><label class="form-label">Full name</label><input name="name" value="{{ old('name') }}" class="form-control" required></div>
  <div class="row g-3">
    <div class="col-sm-6"><label class="form-label">Username</label><input name="username" value="{{ old('username') }}" class="form-control" required></div>
    <div class="col-sm-6"><label class="form-label">Contact number</label><input name="phone" value="{{ old('phone') }}" class="form-control"></div>
  </div>
  <div><label class="form-label">Email</label><input type="email" name="email" value="{{ old('email') }}" class="form-control" required></div>
  <div><label class="form-label">Password</label><input type="password" name="password" class="form-control" autocomplete="new-password" required></div>
  <div><label class="form-label">Confirm password</label><input type="password" name="password_confirmation" class="form-control" autocomplete="new-password" required></div>
  <button class="btn btn-primary btn-lg" type="submit">Create buyer account</button>
  <a class="text-center small fw-bold" href="{{ route('login') }}">Back to sign in</a>
</form>
@endsection
