@extends('layouts.app')
@section('title', 'Team chat')
@section('page', 'chat')
@section('search', 'Search messages')

@section('content')
<div class="page-head">
  <div><h1>Team chat</h1><p>Talk through readings with your group. Water alerts from the ESP32 post here automatically.</p></div>
  <div class="seg" id="chatSeg" role="group" aria-label="Filter messages">
    <button type="button" class="on" data-f="all">All</button>
    <button type="button" data-f="user">Team</button>
    <button type="button" data-f="alert">Alerts</button>
  </div>
</div>

@unless ($name)
  <form class="glass card join" method="POST" action="{{ route('chat.name') }}">
    @csrf
    <h3>Join the room</h3>
    <p class="muted">Choose the name your teammates will see.</p>
    <input class="field" name="name" maxlength="24" placeholder="Your name" value="{{ old('name') }}" required>
    @error('name')<small class="err">{{ $message }}</small>@enderror
    <button class="btn primary" type="submit">Join chat</button>
  </form>
@endunless

<section class="glass msgs" id="msgs" data-f="all" aria-live="polite">
  <p class="hint" id="hint" hidden>No messages yet. Say hi!</p>
</section>

@if ($name)
  <form class="glass composer" id="composer" autocomplete="off">
    <input class="field" id="body" maxlength="500" placeholder="Message as {{ $name }}" required aria-label="Message">
    <button class="btn primary" type="submit" aria-label="Send message"><svg class="i"><use href="#i-send"/></svg><span>Send</span></button>
  </form>
@endif

@push('data')
<script>window.__MSGS = @json($messages);</script>
@endpush
@endsection
