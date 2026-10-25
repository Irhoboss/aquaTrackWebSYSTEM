@extends('layouts.app')
@section('title', 'Attendance')
@section('subtitle', $isStaff ? 'Record your time in and time out, and review your attendance history' : 'Staff attendance report')
@section('content')
@if($isStaff)
  <div class="at-card card-body-pad mb-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
      <div class="text-muted small">Current status</div>
      @if($activeRecord)
        <div class="fw-bold text-success"><i class="bi bi-clock me-1"></i>On duty since @dt($activeRecord->checked_in_at)</div>
      @elseif($isAbsentToday)
        <div class="fw-bold text-danger"><i class="bi bi-calendar-x me-1"></i>Absent today - time in closed at 9:00 AM</div>
      @else
        <div class="fw-bold">Not currently on duty</div>
      @endif
    </div>
    @if($activeRecord)
      <form method="post" action="{{ route('attendance.check-out') }}">@csrf
        <button class="btn btn-danger"><i class="bi bi-box-arrow-right me-1"></i>Time out</button>
      </form>
    @elseif($isAbsentToday)
      <span class="badge text-bg-danger">Absent</span>
    @elseif($isCheckInClosed)
      <span class="badge text-bg-secondary">Time in closed</span>
    @else
      <form method="post" action="{{ route('attendance.check-in') }}">@csrf
        <button class="btn btn-primary"><i class="bi bi-box-arrow-in-right me-1"></i>Time in</button>
      </form>
    @endif
  </div>
@endif

<div class="at-card card-body-pad mb-3">
  <form class="row g-2 align-items-end" method="get">
    @unless($isStaff)
      <div class="col-12 col-md-4">
        <label class="form-label">Staff member</label>
        <select name="user_id" class="form-select">
          <option value="">All staff</option>
          @foreach($staffMembers as $staffMember)
            <option value="{{ $staffMember->id }}" @selected((string) request('user_id') === (string) $staffMember->id)>{{ $staffMember->name }}</option>
          @endforeach
        </select>
      </div>
    @endunless
    <div class="col-6 col-md-3"><label class="form-label">From</label><input type="date" name="from" value="{{ $from }}" class="form-control"></div>
    <div class="col-6 col-md-3"><label class="form-label">To</label><input type="date" name="to" value="{{ $to }}" class="form-control"></div>
    <div class="col-12 col-md-2"><button class="btn btn-primary w-100">Generate report</button></div>
  </form>
</div>

<div class="d-flex gap-2 mb-3">
  <a class="btn btn-soft" href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"><i class="bi bi-download me-1"></i>Download CSV</a>
</div>

<div class="row g-2 mb-3">
  <div class="col-6 col-md-4"><div class="at-tile"><div class="v">{{ number_format($summary['records']) }}</div><div class="l">Attendance records</div></div></div>
  <div class="col-6 col-md-4"><div class="at-tile"><div class="v">{{ number_format($summary['completed']) }}</div><div class="l">Completed shifts</div></div></div>
  <div class="col-6 col-md-4"><div class="at-tile"><div class="v">{{ number_format($summary['currently_checked_in']) }}</div><div class="l">Currently on duty</div></div></div>
</div>

<div class="at-card">
  <div class="card-head"><h2>{{ $isStaff ? 'My attendance history' : 'Staff attendance report' }}</h2></div>
  <div class="table-responsive">
    <table class="table table-hover">
      <thead><tr>@unless($isStaff)<th>Staff</th>@endunless<th>Time in</th><th>Time out</th><th>Duration</th><th>Status</th></tr></thead>
      <tbody>
        @forelse($records as $record)
          <tr>
            @unless($isStaff)<td>{{ $record->user?->name ?? 'Former staff' }}</td>@endunless
            <td class="small">@dt($record->checked_in_at)</td>
            <td class="small">@dt($record->checked_out_at)</td>
            <td>{{ $record->durationLabel() }}</td>
            <td>
              <span class="badge text-bg-{{ $record->checked_out_at ? 'secondary' : 'success' }}">{{ $record->checked_out_at ? 'Completed' : 'On duty' }}</span>
              @if($record->isLate()) <span class="badge text-bg-warning">Late</span> @endif
              @if($record->isEarlyOut()) <span class="badge text-bg-warning">Early out</span> @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="{{ $isStaff ? 4 : 5 }}">@include('partials.empty', ['icon' => 'bi-calendar-check', 'title' => 'No attendance records', 'text' => 'Try a wider date range.'])</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($records->hasPages())<div class="p-3">{{ $records->links() }}</div>@endif
</div>
@endsection
