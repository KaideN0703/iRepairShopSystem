<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AuditLog;
use App\Models\StockMovement;
use App\Models\NotificationsLog;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $module = $request->query('module');
        $action = $request->query('action');
        $search = $request->query('search');

        $logs = AuditLog::with('user')
            ->when($module, function ($q, $module) {
                $q->where('module', $module);
            })
            ->when($action, function ($q, $action) {
                $q->where('action', $action);
            })
            ->when($search, function ($q, $search) {
                $q->where('user_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(20);

        $stockMovements = StockMovement::with(['part', 'user'])->latest()->take(15)->get();
        $notifications = NotificationsLog::latest()->take(15)->get();

        return view('audit_logs.index', compact('logs', 'stockMovements', 'notifications', 'module', 'action', 'search'));
    }
}
