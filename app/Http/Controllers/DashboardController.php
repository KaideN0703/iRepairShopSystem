<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JobOrder;
use App\Models\Part;
use App\Models\Invoice;
use App\Models\AuditLog;
use App\Models\JobOrderStatusHistory;
use App\Services\ForecastingService;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(ForecastingService $forecastingService)
    {
        // 1. Daily Repair Summary & Status Counts
        $statusCounts = [
            'Received' => JobOrder::where('status', 'Received')->count(),
            'Diagnosing' => JobOrder::where('status', 'Diagnosing')->count(),
            'Waiting for Parts' => JobOrder::where('status', 'Waiting for Parts')->count(),
            'Under Repair' => JobOrder::where('status', 'Under Repair')->count(),
            'Testing' => JobOrder::where('status', 'Testing')->count(),
            'Ready for Pickup' => JobOrder::where('status', 'Ready for Pickup')->count(),
            'Completed' => JobOrder::where('status', 'Completed')->count(),
            'Released' => JobOrder::where('status', 'Released')->count(),
        ];

        // Overview Widgets: Pending, Ongoing, Completed, Claimed/Released
        $pendingCount = $statusCounts['Received'] + $statusCounts['Diagnosing'] + $statusCounts['Waiting for Parts'];
        $ongoingCount = $statusCounts['Under Repair'] + $statusCounts['Testing'];
        $completedCount = $statusCounts['Ready for Pickup'] + $statusCounts['Completed'];
        $claimedCount = $statusCounts['Released'];

        // Low stock alerts widget
        $lowStockParts = Part::with('category', 'supplier')
            ->whereColumn('stock_quantity', '<=', 'reorder_level')
            ->get();

        // Monthly income chart data (Last 6 months)
        $incomeForecast = $forecastingService->forecastMonthlyIncome();

        // Recent repair activities feed (latest 10 actions)
        $recentActivities = JobOrderStatusHistory::with('jobOrder.device', 'jobOrder.customer', 'user')
            ->latest()
            ->take(10)
            ->get();

        $totalRevenueMonth = Invoice::where('payment_status', 'paid')
            ->whereYear('issue_date', now()->year)
            ->whereMonth('issue_date', now()->month)
            ->sum('total_amount');

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
