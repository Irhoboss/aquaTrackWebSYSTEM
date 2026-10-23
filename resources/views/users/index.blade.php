@extends('layouts.app')
@section('title', 'Users')
@section('content')
<div class="at-card card-body-pad mb-3">
  <form class="row g-2 align-items-end" method="get">
    <div class="col-12 col-md-4"><label class="form-label">Search</label><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Name, username or email"></div>
    <div class="col-6 col-md-2"><label class="form-label">Role</label><select name="role" class="form-select"><option value="">All</option>@foreach(\App\Models\User::ROLES as $k => $v)<option value="{{ $k }}" @selected(request('role') === $k)>{{ $v }}</option>@endforeach</select></div>
    <div class="col-6 col-md-2"><label class="form-label">Status</label><select name="status" class="form-select"><option value="">All</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="inactive" @selected(request('status') === 'inactive')>Inactive</option></select></div>
    <div class="col-12 col-md-4 d-flex gap-2"><button class="btn btn-primary flex-grow-1">Filter</button><a class="btn btn-soft" href="{{ route('users.index') }}">Reset</a><a class="btn btn-primary" href="{{ route('users.create') }}"><i class="bi bi-person-plus"></i></a></div>
  </form>
</div>
<div class="at-card"><div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>User</th><th>Role</th><th>Status</th><th>Last sign-in</th><th></th></tr></thead><tbody>
@foreach($users as $u)
  <tr><td><div class="d-flex align-items-center gap-2">@if($u->avatar_url)<img class="at-avatar" src="{{ $u->avatar_url }}" alt="">@else<span class="at-avatar">{{ $u->initials }}</span>@endif<div><div class="fw-bold">{{ $u->name }}</div><div class="small text-muted">{{ '@'.$u->username }} · {{ $u->email }}</div></div></div></td>
    <td>{{ $u->role_label }}</td><td><span class="pill pill-{{ $u->is_active ? 'ok' : 'muted' }}">{{ $u->is_active ? 'Active' : 'Inactive' }}</span></td>
    <td class="small">{{ $u->last_login_at ? \App\Support\Fmt::dt($u->last_login_at) : '—' }}</td>
    <td class="text-end"><a class="btn-circle" href="{{ route('users.show', $u) }}" aria-label="Open {{ $u->name }}"><i class="bi bi-arrow-right"></i></a></td></tr>
@endforeach
</tbody></table></div><div class="px-3 pt-3">{{ $users->links() }}</div></div>
@endsection
