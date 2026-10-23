<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use App\Support\Csv;
use App\Support\Fmt;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $types = ReportService::TYPES;
        if (!$request->user()->can('reports.sensitive')) unset($types['user_activity']);

        $type = array_key_exists($request->input('type'), $types) ? $request->input('type') : 'ph_summary';
        abort_if($type === 'user_activity' && !$request->user()->can('reports.sensitive'), 403);

        $to = $request->input('to', now(Fmt::tz())->toDateString());
        $from = $request->input('from', now(Fmt::tz())->subDays(29)->toDateString());
        $group = in_array($request->group, ['day', 'week', 'month']) ? $request->group : 'day';

        $report = ReportService::build($type, $from, $to, $group);

        if ($request->input('export') === 'csv') {
            return Csv::download($type.'-'.now()->format('Ymd').'.csv', $report['columns'], $report['rows']);
        }
        $view = $request->boolean('print') ? 'reports.print' : 'reports.index';
        return view($view, compact('report', 'types', 'type', 'from', 'to', 'group'));
    }
}
