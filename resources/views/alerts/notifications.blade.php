@extends('layouts.app')
@section('title', 'Notifications')
@section('content')
<div class="at-card">
  <div class="card-head"><h2>Notification history</h2>
    <form method="post" action="{{ route('notifications.readall') }}" data-no-loading="1">@csrf<button class="btn btn-soft btn-sm">Mark all as read</button></form></div>
  @forelse($notifications as $n)
    <div class="d-flex gap-3 align-items-start px-3 py-3 border-top" style="border-color:var(--at-line)!important">
      <span class="dot {{ $n->read_at ? '' : 'on' }} mt-2"></span>
      <div class="flex-grow-1"><div class="fw-bold">{{ $n->title }}</div><div class="small text-muted">{{ $n->body }}</div><div class="small text-muted">@dt($n->created_at)</div></div>
      <div class="d-flex gap-2">
        @if(!$n->read_at)<form method="post" action="{{ route('notifications.read', $n) }}" data-no-loading="1">@csrf<button class="btn btn-soft btn-sm">Mark read</button></form>@endif
        @if($n->url)<form method="post" action="{{ route('notifications.read', $n) }}?go=1" data-no-loading="1">@csrf<button class="btn btn-circle" aria-label="Open"><i class="bi bi-arrow-right"></i></button></form>@endif
      </div>
    </div>
  @empty @include('partials.empty', ['icon' => 'bi-bell-slash', 'title' => 'No notifications yet']) @endforelse
  <div class="p-3">{{ $notifications->links() }}</div>
</div>
@endsection
