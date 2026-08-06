<?php

namespace App\Services;

use App\Models\Part;
use App\Models\JobOrderPart;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class ForecastingService
{
    /**
     * Forecast inventory demand for parts based on 30-day historical consumption moving average.
     */
    public function forecastInventoryDemand(): array
    {
        $parts = Part::with('category')->get();
        $forecasts = [];

        foreach ($parts as $part) {
            // Count total units consumed in last 30 days
            $unitsUsed30Days = JobOrderPart::where('part_id', $part->id)
                ->where('created_at', '>=', now()->subDays(30))
                ->sum('quantity');

            // Daily average
            $dailyAverage = $unitsUsed30Days / 30;

            // Projected 30-day demand
            $projectedDemand30Days = ceil($dailyAverage * 30);
            if ($projectedDemand30Days == 0) {
                $projectedDemand30Days = 2; // Default safety threshold
            }

            $daysOfStockLeft = $dailyAverage > 0 ? round($part->stock_quantity / $dailyAverage, 1) : 999;
            $needsReorder = $part->stock_quantity <= $projectedDemand30Days || $part->isLowStock();

            $forecasts[] = [
                'part_id' => $part->id,
                'sku' => $part->sku,
                'name' => $part->name,
                'category' => $part->category?->name ?? 'Uncategorized',
                'current_stock' => $part->stock_quantity,
                'reorder_level' => $part->reorder_level,
                'units_used_30_days' => $unitsUsed30Days,
                'projected_30day_demand' => $projectedDemand30Days,
                'days_of_stock_left' => $daysOfStockLeft == 999 ? '30+ Days' : "$daysOfStockLeft Days",
                'suggested_reorder_qty' => max(0, ($projectedDemand30Days * 2) - $part->stock_quantity),
                'needs_reorder' => $needsReorder,
            ];
        }

        return $forecasts;
    }

    /**
     * Forecast monthly income based on 3-month historical moving average trend.
     */
    public function forecastMonthlyIncome(): array
    {
        $months = [];
        $historical = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthKey = $date->format('Y-m');
            $label = $date->format('M Y');

            $revenue = Invoice::where('payment_status', 'paid')
                ->whereYear('issue_date', $date->year)
                ->whereMonth('issue_date', $date->month)
                ->sum('total_amount');

            $months[] = $label;
            $historical[] = (float) $revenue;
        }

        // Calculate 3-month moving average
        $last3 = array_slice($historical, -3);
        $movingAvg = count($last3) > 0 ? array_sum($last3) / count($last3) : 0;
        
        // Add 5% growth projection for next month
        $projectedNextMonth = round($movingAvg * 1.05, 2);

        return [
            'labels' => array_merge($months, ['Projected Next Month']),
            'historical' => $historical,
            'moving_average' => round($movingAvg, 2),
            'projected_next_month' => $projectedNextMonth,
        ];
    }
}
