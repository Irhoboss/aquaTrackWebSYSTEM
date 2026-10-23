<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\Fmt;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $logs = AuditLog::with('user')
            ->when($request->action, fn ($q, $a) => $q->where('action', $a))
            ->when($request->user_id, fn ($q, $u) => $q->where('user_id', $u))
            ->when($request->from, fn ($q) => $q->where('created_at', '>=', Fmt::dayStart($request->from)))
            ->when($request->to, fn ($q) => $q->where('created_at', '<=', Fmt::dayEnd($request->to)))
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('subject_label', 'like', "%{$s}%")->orWhere('action', 'like', "%{$s}%")->orWhere('ip', 'like', "%{$s}%")))
            ->latest('created_at')->paginate(25)->withQueryString();

        return view('audit.index', [
            'logs' => $logs,
            'actions' => AuditLog::select('action')->distinct()->orderBy('action')->pluck('action'),
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
