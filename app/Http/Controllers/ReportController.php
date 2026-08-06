<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JobOrder;
use App\Models\Technician;
use App\Models\Part;
use App\Models\JobOrderPart;
use App\Models\Invoice;
use App\Models\Customer;
use App\Services\ForecastingService;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request, ForecastingService $forecastingService)
    {
        $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', now()->endOfMonth()->toDateString());

        // 1. Repair Metrics
        $totalJobs = JobOrder::whereBetween('created_at', [$startDate, $endDate])->count();
        $totalCompletedJobs = JobOrder::whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['Ready for Pickup', 'Completed', 'Released'])
            ->count();

        // 2. Financial Income Report
        $totalRevenue = Invoice::where('payment_status', 'paid')
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->sum('total_amount');

        $laborRevenue = Invoice::where('payment_status', 'paid')
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->join('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->where('invoice_items.item_type', 'labor')
            ->sum('invoice_items.total_price');

        $partsRevenue = Invoice::where('payment_status', 'paid')
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->join('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->where('invoice_items.item_type', 'part')
            ->sum('invoice_items.total_price');

        $partsCost = JobOrderPart::join('job_orders', 'job_order_parts.job_order_id', '=', 'job_orders.id')
            ->whereBetween('job_orders.created_at', [$startDate, $endDate])
            ->join('parts', 'job_order_parts.part_id', '=', 'parts.id')
            ->sum(DB::raw('job_order_parts.quantity * COALESCE(parts.cost_price, 0)'));

        $totalPartsProfit = max(0, $partsRevenue - $partsCost);

        $outstandingBalance = Invoice::whereIn('payment_status', ['unpaid', 'partial'])
            ->sum(DB::raw('total_amount - paid_amount'));

        // 3. Technician Performance Metrics
        $techPerformance = Technician::withCount([
            'jobOrders as period_completed_count' => function ($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate])
                    ->whereIn('status', ['Ready for Pickup', 'Completed', 'Released']);
            }
        ])->get();

        // 4. Best Selling Parts Report
        $bestSellingParts = JobOrderPart::select('part_id', DB::raw('SUM(quantity) as total_used'), DB::raw('SUM(total_price) as total_revenue'))
            ->with('part.category')
            ->groupBy('part_id')
            ->orderByDesc('total_used')
            ->take(10)
            ->get();

        // 5. Income Forecast & Demand Forecast
        $incomeForecast = $forecastingService->forecastMonthlyIncome();
        $inventoryForecast = $forecastingService->forecastInventoryDemand();

        return view('reports.index', compact(
            'startDate',
            'endDate',
            'totalJobs',
            'totalCompletedJobs',
            'totalRevenue',
            'laborRevenue',
            'partsRevenue',
            'totalPartsProfit',
            'outstandingBalance',
            'techPerformance',
            'bestSellingParts',
            'incomeForecast',
            'inventoryForecast'
        ));
    }
}
