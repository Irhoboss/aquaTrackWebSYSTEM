@extends('layouts.guest')
@section('title', $title)
@section('heading', $title)
@section('lead', 'AquaTrack · Draft policy version 1.0')
@section('content')
<div class="legal-content">
  <p class="notice notice-info">These plain-language policies use conservative defaults. They are not legal advice. The operator must confirm the terms and applicable law before public launch.</p>
  <section class="legal-business" aria-labelledby="legal-business-heading">
    <h3 id="legal-business-heading">Business details</h3>
    <dl>
      <dt>Legal operator</dt><dd>{{ $operatorName ?: 'Not configured — enter the legal business name in Settings.' }}</dd>
      <dt>Station</dt><dd>{{ $stationName ?: 'Not configured' }}</dd>
      <dt>Address</dt><dd>{{ $stationAddress ?: 'Not configured — enter the business address in Settings.' }}</dd>
      <dt>Station contact</dt><dd>{{ $stationContact ?: 'Not configured' }}</dd>
      <dt>Privacy contact</dt><dd>@if($privacyContact)<a href="mailto:{{ $privacyContact }}">{{ $privacyContact }}</a>@else Not configured — enter a privacy contact email in Settings.@endif</dd>
    </dl>
  </section>
  @foreach($sections as [$heading, $paragraphs])
    <section class="legal-section">
      <h3>{{ $heading }}</h3>
      @foreach($paragraphs as $paragraph)<p>{{ $paragraph }}</p>@endforeach
    </section>
  @endforeach
  <p class="small text-muted">Effective date: November 16, 2026 · Version 1.0</p>
  <a class="btn btn-soft" href="{{ route('home') }}">Return to AquaTrack</a>
</div>
@endsection
