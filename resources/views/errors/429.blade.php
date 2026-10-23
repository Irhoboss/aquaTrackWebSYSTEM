@extends('layouts.guest')
@section('title', '429')
@section('heading', '429 · Too many requests')
@section('lead', 'Please wait a moment before trying again.')
@section('content')
<div class="d-grid gap-2"><a class="btn btn-primary" href="{{ url('/') }}">Back to dashboard</a><a class="btn btn-soft" href="javascript:history.back()">Go back</a></div>
@endsection
