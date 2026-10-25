<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\User;
use App\Support\Audit;
use App\Support\Csv;
use App\Support\Fmt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isStaff = $user->role === 'staff';
        $today = now(Fmt::tz())->toDateString();
        $from = $request->input('from', now(Fmt::tz())->subDays(29)->toDateString());
        $to = $request->input('to', $today);

        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'user_id' => ['nullable', 'integer'],
        ]);
        $from = $filters['from'] ?? $from;
        $to = $filters['to'] ?? $to;

        $query = $this->recordsQuery($user, $from, $to);
        if (! $isStaff && ! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        $summaryQuery = clone $query;
        $summary = [
            'records' => (clone $summaryQuery)->count(),
            'completed' => (clone $summaryQuery)->whereNotNull('checked_out_at')->count(),
            'currently_checked_in' => (clone $summaryQuery)->whereNull('checked_out_at')->count(),
        ];

        if ($request->input('export') === 'csv') {
            $rows = (function () use ($query) {
                $exportQuery = (clone $query)
                    ->join('users', 'users.id', '=', 'attendance_records.user_id')
                    ->select('attendance_records.*', 'users.name as staff_name');

                foreach ($exportQuery->cursor() as $record) {
                    $status = [$record->checked_out_at ? 'Completed' : 'On duty'];
                    if ($record->isLate()) {
                        $status[] = 'Late';
                    }
                    if ($record->isEarlyOut()) {
                        $status[] = 'Early out';
                    }

                    yield [
                        $record->staff_name,
                        Fmt::dt($record->checked_in_at, 'Y-m-d H:i:s'),
                        Fmt::dt($record->checked_out_at, 'Y-m-d H:i:s'),
                        $record->durationLabel(),
                        implode(', ', $status),
                    ];
                }
            })();

            return Csv::download('attendance-'.now()->format('Ymd').'.csv', [
                'Staff', 'Time in', 'Time out', 'Duration', 'Status',
            ], $rows);
        }

        $records = $query->with('user')->paginate(25)->withQueryString();
        $staffMembers = $isStaff ? collect() : User::query()
            ->where('role', 'staff')
            ->orderBy('name')
            ->get(['id', 'name']);
        $activeRecord = $isStaff
            ? $user->attendanceRecords()->whereNull('checked_out_at')->latest('checked_in_at')->first()
            : null;
        $hasCheckedInToday = $isStaff && $user->attendanceRecords()
            ->whereBetween('checked_in_at', [Fmt::dayStart($today), Fmt::dayEnd($today)])
            ->exists();
        $isCheckInClosed = $isStaff && now(Fmt::tz())->format('H:i:s') >= '09:00:00';
        $isAbsentToday = $isCheckInClosed && ! $activeRecord && ! $hasCheckedInToday;

        return view('attendance.index', compact(
            'records', 'summary', 'staffMembers', 'activeRecord', 'isAbsentToday', 'isCheckInClosed', 'isStaff', 'from', 'to'
        ));
    }

    public function checkIn(Request $request)
    {
        abort_unless($request->user()->role === 'staff', 403);

        $record = DB::transaction(function () use ($request) {
            User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();

            if ($request->user()->attendanceRecords()->whereNull('checked_out_at')->exists()) {
                return null;
            }

            $checkedInAt = now(Fmt::tz());
            if ($checkedInAt->format('H:i:s') >= '09:00:00') {
                return false;
            }

            return $request->user()->attendanceRecords()->create([
                'checked_in_at' => $checkedInAt->utc(),
            ]);
        });

        if ($record === false) {
            return back()->with('error', 'Time in is closed from 9:00 AM. You are marked absent for today.');
        }

        if (! $record) {
            return back()->with('error', 'Your time in is already recorded. Please record your time out first.');
        }

        Audit::log('attendance.checked_in', $record);

        return back()->with('success', 'Time in recorded at '.Fmt::dt($record->checked_in_at).'.');
    }

    public function checkOut(Request $request)
    {
        abort_unless($request->user()->role === 'staff', 403);

        $record = DB::transaction(function () use ($request) {
            User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();

            $activeRecord = $request->user()->attendanceRecords()
                ->whereNull('checked_out_at')
                ->latest('checked_in_at')
                ->lockForUpdate()
                ->first();

            if (! $activeRecord) {
                return null;
            }

            $activeRecord->update(['checked_out_at' => now()]);

            return $activeRecord;
        });

        if (! $record) {
            return back()->with('error', 'There is no active shift to record a time out for.');
        }

        Audit::log('attendance.checked_out', $record);

        return back()->with('success', 'Time out recorded at '.Fmt::dt($record->checked_out_at).'.');
    }

    private function recordsQuery(User $user, string $from, string $to)
    {
        return AttendanceRecord::query()
            ->whereBetween('checked_in_at', [Fmt::dayStart($from), Fmt::dayEnd($to)])
            ->when($user->role === 'staff', fn ($query) => $query->where('user_id', $user->id))
            ->when($user->role !== 'staff', fn ($query) => $query->whereHas(
                'user', fn ($staff) => $staff->where('role', 'staff')
            ))
            ->latest('checked_in_at');
    }
}
