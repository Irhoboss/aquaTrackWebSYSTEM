@extends('layouts.guest')
@section('title', 'Register')
@section('heading', 'Register')
@section('lead', 'Verify your email with a one-time code to request a buyer account.')
@section('content')
@include('auth.partials.register-form')
@endsection
