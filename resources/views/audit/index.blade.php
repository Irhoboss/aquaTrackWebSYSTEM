@extends('layouts.app')
@section('title', 'Audit log')
@section('subtitle', 'Who did what, and when')
@section('content')
<div class="at-card card-body-pad mb-3">
  <form class="row g-2 align-items-end" method="get">
    <div class="col-12 col-md-3"><label class="form-label">Search</label><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Action, record or IP"></div>
    <div class="col-6 col-md-2"><label class="form-label">Action</label><select name="action" class="form-select"><option value="">All</option>@foreach($actions as $a)<option @selected(request('action') === $a)>{{ $a }}</option>@endforeach</select></div>
    <div class="col-6 col-md-2"><label class="form-label">User</label><select name="user_id" class="form-select"><option value="">All</option>@foreach($users as $u)<option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>{{ $u->name }}</option>@endforeach</select></div>
    <div class="col-6 col-md-2"><label class="form-label">From</label><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
    <div class="col-6 col-md-2"><label class="form-label">To</label><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
    <div class="col-12 col-md-1"><button class="btn btn-primary w-100" aria-label="Apply filters"><i class="bi bi-funnel"></i></button></div>
    <div class="col-12"><a class="btn btn-soft btn-sm" href="{{ route('audit.index') }}">Reset filters</a></div>
  </form>
</div>
<div class="at-card"><div class="table-responsive"><table class="table table-hover"><thead><tr><th>When</th><th>User</th><th>Action</th><th>Record</th><th>Details</th><th>IP</th></tr></thead><tbody>
@forelse($logs as $l)
  <tr><td class="small">@dt($l->created_at)</td><td>{{ $l->user?->name ?? 'System / guest' }}</td><td><span class="pill pill-info">{{ $l->action }}</span></td><td class="small">{{ $l->subject_type }} {{ $l->subject_label }}</td>
    <td class="small text-muted">@if($l->details){{ collect($l->details)->map(fn ($v, $k) => $k.': '.(is_array($v) ? implode(',', $v) : $v))->implode(' · ') }}@endif</td><td class="small text-muted">{{ $l->ip }}</td></tr>
@empty<tr><td colspan="6">@include('partials.empty', ['icon' => 'bi-journal-text', 'title' => 'No log entries match'])</td></tr>@endforelse
</tbody></table></div><div class="px-3 pt-3">{{ $logs->links() }}</div></div>
@endsection
