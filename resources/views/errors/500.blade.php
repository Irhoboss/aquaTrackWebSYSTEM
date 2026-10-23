@extends('layouts.guest')
@section('title', '500')
@section('heading', '500 · Something went wrong')
@section('lead', 'An unexpected error occurred. It has been logged. Please try again.')
@section('content')
<div class="d-grid gap-2"><a class="btn btn-primary" href="{{ url('/') }}">Back to dashboard</a><a class="btn btn-soft" href="javascript:history.back()">Go back</a></div>
@endsection
