<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\AuditLog;
use App\Models\Container;
use App\Models\InventoryItem;
use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        return view('dashboard.index', [
            'stats' => DashboardService::stats($user),
            'alerts' => Alert::open()->visibleTo($user)->latest()->limit(5)->get(),
            'lowStock' => InventoryItem::with('category')->where('status', 'active')->whereColumn('quantity', '<=', 'min_stock')->orderBy('quantity')->limit(6)->get(),
            'inspections' => Container::where('status', '!=', 'retired')->whereNotNull('next_inspection_date')->orderBy('next_inspection_date')->limit(6)->get(),
            'activity' => AuditLog::with('user')
                ->when(!$user->can('audit.view'), fn ($q) => $q->where('user_id', $user->id))
                ->latest('created_at')->limit(8)->get(),
        ]);
    }

    /** JSON polled by the dashboard (no full page reload). */
    public function stats(Request $request)
    {
        return response()->json(DashboardService::stats($request->user()));
    }
}
