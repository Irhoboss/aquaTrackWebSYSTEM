<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Support\Audit;
use App\Support\Fmt;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index(Request $request)
    {
        $alerts = Alert::with(['acknowledger', 'resolver', 'device'])->visibleTo($request->user())
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->severity, fn ($q, $v) => $q->where('severity', $v))
            ->when($request->type, fn ($q, $v) => $q->where('type', $v))
            ->when($request->from, fn ($q) => $q->where('created_at', '>=', Fmt::dayStart($request->from)))
            ->when($request->to, fn ($q) => $q->where('created_at', '<=', Fmt::dayEnd($request->to)))
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('title', 'like', "%{$s}%")->orWhere('description', 'like', "%{$s}%")->orWhere('related_label', 'like', "%{$s}%")->orWhere('id', ltrim($s, '#'))))
            ->latest()->paginate(15)->withQueryString();

        return view('alerts.index', compact('alerts'));
    }

    public function acknowledge(Request $request, Alert $alert)
    {
        if ($alert->status === 'new') {
            $alert->update(['status' => 'acknowledged', 'acknowledged_by' => $request->user()->id, 'acknowledged_at' => now()]);
            Audit::log('alert.acknowledged', $alert, ['title' => $alert->title]);
        }
        return back()->with('success', 'Alert acknowledged.');
    }

    public function resolve(Request $request, Alert $alert)
    {
        $data = $request->validate(['resolution_notes' => 'required|string|max:1000']);
        if ($alert->status !== 'resolved') {
            $alert->update(['status' => 'resolved', 'resolved_by' => $request->user()->id, 'resolved_at' => now(), 'resolution_notes' => $data['resolution_notes']]
                + ($alert->acknowledged_by ? [] : ['acknowledged_by' => $request->user()->id, 'acknowledged_at' => now()]));
            Audit::log('alert.resolved', $alert, ['title' => $alert->title]);
        }
        return back()->with('success', 'Alert resolved.');
    }
}
