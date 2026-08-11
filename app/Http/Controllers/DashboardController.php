<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JobOrder;
use App\Models\Part;
use App\Models\Invoice;
use App\Models\AuditLog;
use App\Models\JobOrderStatusHistory;
use App\Services\ForecastingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(ForecastingService $forecastingService)
    {
        $user = Auth::user();

        // ── Job status counts (scoped by role) ──────────────────────────────
        if ($user->can('dashboard.view.all') || $user->can('repairs.view.status') || $user->can('jobs.manage.full') || $user->can('dashboard.view.inventory')) {
            // shop_manager / admin / cashier / inventory_staff: full shop-wide view
            $statusCounts = [
                'Received'          => JobOrder::where('status', 'Received')->count(),
                'Diagnosing'        => JobOrder::where('status', 'Diagnosing')->count(),
                'Waiting for Parts' => JobOrder::where('status', 'Waiting for Parts')->count(),
                'Under Repair'      => JobOrder::where('status', 'Under Repair')->count(),
                'Testing'           => JobOrder::where('status', 'Testing')->count(),
                'Ready for Pickup'  => JobOrder::where('status', 'Ready for Pickup')->count(),
                'Completed'         => JobOrder::where('status', 'Completed')->count(),
                'Released'          => JobOrder::where('status', 'Released')->count(),
            ];
        } else {
            // technician: only their assigned jobs
            $technicianId = $user->technician?->id;
            $statusCounts = [
                'Received'          => JobOrder::where('status', 'Received')->where('technician_id', $technicianId)->count(),
                'Diagnosing'        => JobOrder::where('status', 'Diagnosing')->where('technician_id', $technicianId)->count(),
                'Waiting for Parts' => JobOrder::where('status', 'Waiting for Parts')->where('technician_id', $technicianId)->count(),
                'Under Repair'      => JobOrder::where('status', 'Under Repair')->where('technician_id', $technicianId)->count(),
                'Testing'           => JobOrder::where('status', 'Testing')->where('technician_id', $technicianId)->count(),
                'Ready for Pickup'  => JobOrder::where('status', 'Ready for Pickup')->where('technician_id', $technicianId)->count(),
                'Completed'         => JobOrder::where('status', 'Completed')->where('technician_id', $technicianId)->count(),
                'Released'          => JobOrder::where('status', 'Released')->where('technician_id', $technicianId)->count(),
            ];
        }

        $pendingCount   = ($statusCounts['Received'] ?? 0) + ($statusCounts['Diagnosing'] ?? 0) + ($statusCounts['Waiting for Parts'] ?? 0);
        $ongoingCount   = ($statusCounts['Under Repair'] ?? 0) + ($statusCounts['Testing'] ?? 0);
        $completedCount = ($statusCounts['Ready for Pickup'] ?? 0) + ($statusCounts['Completed'] ?? 0);
        $claimedCount   = $statusCounts['Released'] ?? 0;

        // ── Low-stock widget ────────────────────────────────────────────────
        $lowStockParts = ($user->can('dashboard.view.inventory') || $user->can('dashboard.view.all') || $user->can('dashboard.view.own'))
            ? Part::with('category', 'supplier')->whereColumn('stock_quantity', '<=', 'reorder_level')->get()
            : collect();

        // ── Monthly income forecast + revenue (sales/all permission) ─────────
        $incomeForecast = ($user->can('dashboard.view.all') || $user->can('dashboard.view.sales'))
            ? $forecastingService->forecastMonthlyIncome()
            : ['labels' => [], 'historical' => [], 'moving_average' => 0, 'projected_next_month' => 0];

        $totalRevenueMonth = ($user->can('dashboard.view.all') || $user->can('dashboard.view.sales'))
            ? Invoice::where('payment_status', 'paid')
                ->whereYear('issue_date', now()->year)
                ->whereMonth('issue_date', now()->month)
                ->sum('total_amount')
            : null;

        // ── Recent activity feed ─────────────────────────────────────────────
        $recentActivities = JobOrderStatusHistory::with('jobOrder.device', 'jobOrder.customer', 'user')
            ->latest()
            ->take(10)
            ->get();

        return view('dashboard.index', compact(
            'statusCounts',
            'pendingCount',
            'ongoingCount',
            'completedCount',
            'claimedCount',
            'lowStockParts',
            'incomeForecast',
            'recentActivities',
            'totalRevenueMonth'
        ));
    }
}
