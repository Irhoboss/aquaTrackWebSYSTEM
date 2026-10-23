@extends('layouts.guest')
@section('title', '404')
@section('heading', '404 · Page not found')
@section('lead', 'The page you are looking for does not exist or was moved.')
@section('content')
<div class="d-grid gap-2"><a class="btn btn-primary" href="{{ url('/') }}">Back to dashboard</a><a class="btn btn-soft" href="javascript:history.back()">Go back</a></div>
@endsection
