@extends('layouts.app')
@section('title', 'My profile')
@section('content')
<div class="profile-layout">
  <div>
    <div class="at-card card-body-pad text-center">
      @if($user->avatar_url)<img id="avatarPreview" src="{{ $user->avatar_url }}" class="at-avatar lg mb-3" alt="Profile photo">
      @else<span id="avatarInitials" class="at-avatar lg mb-3">{{ $user->initials }}</span><img id="avatarPreview" class="at-avatar lg mb-3 d-none" alt="Profile photo preview">@endif
      <h2 class="h5 mb-0">{{ $user->name }}</h2>
      <div class="text-muted small mb-2">{{ '@'.$user->username }}</div>
      <span class="pill pill-info">{{ $user->role_label }}</span> <span class="pill pill-{{ $user->is_active ? 'ok' : 'muted' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span>
      <hr>
      <dl class="text-start small mb-0">
        <dt>Account created</dt><dd>@dt($user->created_at)</dd>
        <dt>Last sign-in</dt><dd class="mb-0">{{ $user->last_login_at ? \App\Support\Fmt::dt($user->last_login_at) : '—' }}</dd>
      </dl>
    </div>
  </div>

  <div class="vstack gap-3">
    <div class="at-card card-body-pad">
      <h2 class="h6 mb-3">Edit profile</h2>
      <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="row g-3">
        @csrf @method('PUT')
        <div class="col-md-6"><label class="form-label">Full name</label><input name="name" value="{{ old('name', $user->name) }}" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Username</label><input name="username" value="{{ old('username', $user->username) }}" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Contact number</label><input name="phone" value="{{ old('phone', $user->phone) }}" class="form-control"></div>
        <div class="col-12"><label class="form-label">Profile photo <span class="text-muted fw-normal">(JPG, PNG or WebP, max 2 MB)</span></label>
          <input type="file" name="avatar" id="avatar" accept="image/png,image/jpeg,image/webp" class="form-control"></div>
        <div class="col-12 d-flex gap-2"><button class="btn btn-primary">Save changes</button>
          @if($user->avatar)<button class="btn btn-soft" form="removeAvatar" type="submit">Remove photo</button>@endif</div>
      </form>
      @if($user->avatar)<form id="removeAvatar" method="post" action="{{ route('profile.avatar.remove') }}" data-confirm="Remove your profile photo?">@csrf @method('DELETE')</form>@endif
      <div class="small text-muted mt-2">Your role and account status can only be changed by an administrator.</div>
    </div>

    <div class="at-card card-body-pad" id="password">
      <h2 class="h6 mb-3">Change password</h2>
      <form method="post" action="{{ route('profile.password') }}" class="row g-3">
        @csrf @method('PUT')
        <div class="col-12"><label class="form-label">Current password</label><input type="password" name="current_password" class="form-control" autocomplete="current-password" required></div>
        <div class="col-md-6"><label class="form-label">New password</label><input type="password" name="password" class="form-control" autocomplete="new-password" required></div>
        <div class="col-md-6"><label class="form-label">Confirm new password</label><input type="password" name="password_confirmation" class="form-control" autocomplete="new-password" required></div>
        <div class="col-12"><button class="btn btn-primary">Update password</button></div>
      </form>
    </div>

    <div class="at-card">
      <div class="card-head"><h2>My activity</h2></div>
      @forelse($activity as $l)
        <div class="px-3 py-2 border-top" style="border-color:var(--at-line)!important"><div class="fw-bold small">{{ $l->action }} <span class="fw-normal text-muted">{{ $l->subject_label }}</span></div><div class="small text-muted">@dt($l->created_at)</div></div>
      @empty @include('partials.empty', ['icon' => 'bi-journal', 'title' => 'No activity yet']) @endforelse
      <div class="p-3">{{ $activity->links() }}</div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('avatar').addEventListener('change', function () {
  const f = this.files[0]; if (!f) return;
  if (f.size > 2 * 1024 * 1024) { alert('Image must be 2 MB or smaller.'); this.value = ''; return; }
  const img = document.getElementById('avatarPreview'); img.src = URL.createObjectURL(f); img.classList.remove('d-none');
  const ini = document.getElementById('avatarInitials'); if (ini) ini.classList.add('d-none');
});
</script>
@endpush
