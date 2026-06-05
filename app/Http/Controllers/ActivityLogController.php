<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user')->latest();

        if ($request->filled('action'))   $query->where('action', $request->action);
        if ($request->filled('user_id'))  $query->where('user_id', $request->user_id);
        if ($request->filled('area'))     $query->where('area_name', 'like', '%' . $request->area . '%');
        if ($request->filled('year'))     $query->where('year', $request->year);
        if ($request->filled('quarter'))  $query->where('quarter', $request->quarter);
        if ($request->filled('from'))     $query->whereDate('created_at', '>=', $request->from);
        if ($request->filled('to'))       $query->whereDate('created_at', '<=', $request->to);

        $logs    = $query->paginate(50)->withQueryString();
        $actions = ActivityLog::distinct()->pluck('action')->sort()->values();
        $users   = \App\Models\User::orderBy('name')->get(['id','name','pis_number']);

        // List available quarterly log files
        $logFiles = collect(Storage::disk('local')->files('activity_logs'))
            ->filter(fn($f) => str_ends_with($f, '.log'))
            ->sort()->reverse()->values();

        return view('activity-logs.index', compact('logs', 'actions', 'users', 'logFiles'));
    }

    public function downloadFile(Request $request)
    {
        $file = $request->get('file');
        // Sanitise — only allow files inside activity_logs/
        $safe = basename($file);
        $path = 'activity_logs/' . $safe;

        if (!Storage::disk('local')->exists($path)) {
            abort(404, 'Log file not found.');
        }

        return response()->streamDownload(function () use ($path) {
            echo Storage::disk('local')->get($path);
        }, $safe, ['Content-Type' => 'text/plain']);
    }
}
