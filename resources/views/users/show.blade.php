@extends('layouts.app')
@section('title', $user->name)
@section('subtitle', '@'.$user->username)
@section('content')
<div class="row g-3">
  <div class="col-lg-4"><div class="at-card card-body-pad text-center">
    @if($user->avatar_url)<img class="at-avatar lg mb-3" src="{{ $user->avatar_url }}" alt="">@else<span class="at-avatar lg mb-3">{{ $user->initials }}</span>@endif
    <div><span class="pill pill-info">{{ $user->role_label }}</span> <span class="pill pill-{{ $user->is_active ? 'ok' : 'muted' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span></div>
    <dl class="text-start small mt-3 mb-0"><dt>Email</dt><dd>{{ $user->email }}</dd><dt>Phone</dt><dd>{{ $user->phone ?: '—' }}</dd><dt>Created</dt><dd>@dt($user->created_at)</dd><dt>Last sign-in</dt><dd class="mb-0">{{ $user->last_login_at ? \App\Support\Fmt::dt($user->last_login_at) : '—' }}</dd></dl>
    <div class="d-grid gap-2 mt-3"><a class="btn btn-soft" href="{{ route('users.edit', $user) }}">Edit account</a>
      <form method="post" action="{{ route('users.reset', $user) }}" data-confirm="Reset this user’s password? They will be given a new temporary password.">@csrf<button class="btn btn-soft w-100">Reset password</button></form></div>
  </div></div>
  <div class="col-lg-8"><div class="at-card"><div class="card-head"><h2>Activity</h2></div>
    @forelse($activity as $l)<div class="px-3 py-2 border-top" style="border-color:var(--at-line)!important"><div class="fw-bold small">{{ $l->action }} <span class="fw-normal text-muted">{{ $l->subject_label }}</span></div><div class="small text-muted">@dt($l->created_at) · {{ $l->ip }}</div></div>
    @empty @include('partials.empty', ['icon' => 'bi-journal', 'title' => 'No activity yet']) @endforelse
    <div class="p-3">{{ $activity->links() }}</div></div></div>
</div>
@endsection
