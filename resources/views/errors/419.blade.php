@extends('layouts.guest')
@section('title', '419')
@section('heading', '419 · Session expired')
@section('lead', 'Your session timed out. Refresh the page and try again.')
@section('content')
<div class="d-grid gap-2"><a class="btn btn-primary" href="{{ url('/') }}">Back to dashboard</a><a class="btn btn-soft" href="javascript:history.back()">Go back</a></div>
@endsection
