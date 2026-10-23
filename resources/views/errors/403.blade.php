@extends('layouts.guest')
@section('title', '403')
@section('heading', '403 · Access denied')
@section('lead', 'You do not have permission to open this page.')
@section('content')
<div class="d-grid gap-2"><a class="btn btn-primary" href="{{ url('/') }}">Back to dashboard</a><a class="btn btn-soft" href="javascript:history.back()">Go back</a></div>
@endsection
