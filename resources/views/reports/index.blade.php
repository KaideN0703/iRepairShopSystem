@extends('layouts.app')

@section('title', 'Reports & Analytics Dashboard')

@section('content')
<div class="space-y-6">

    <!-- Date Filter Bar -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-ir-carbon border border-ir-copper p-5 rounded-md">
        <div>
            <h2 class="text-xl font-bold text-ir-bone">Business Reports & Predictive Analytics</h2>
            <p class="text-xs text-ir-bone/70 mt-1">Financial performance, technician turnaround, top parts, and trend forecasting</p>
        </div>

        <form action="{{ route('reports.index') }}" method="GET" class="flex items-center gap-3 w-full sm:w-auto">
            <input type="date" name="start_date" value="{{ $startDate }}" class="px-3 py-2 rounded-md bg-ir-void border border-ir-copper text-xs text-ir-bone">
            <span class="text-ir-copper text-xs">to</span>
            <input type="date" name="end_date" value="{{ $endDate }}" class="px-3 py-2 rounded-md bg-ir-void border border-ir-copper text-xs text-ir-bone">
            <button type="submit" class="px-4 py-2 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone font-medium text-xs">
                Update Range
            </button>
        </form>
    </div>

    <!-- Overview Financial & Repair Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        @if($totalRevenue !== null)
        <div class="bg-ir-carbon border border-ir-copper rounded-md p-5">
            <span class="text-xs font-semibold text-ir-bone/70 uppercase tracking-wider">Total Service Revenue</span>
            <h3 class="text-2xl font-extrabold text-emerald-400 mt-1">₱{{ number_format($totalRevenue, 2) }}</h3>
            <span class="text-xs text-emerald-400/80 mt-1 inline-block font-mono">Paid & completed jobs</span>
        </div>
        @endif

        @if($totalPartsProfit !== null)
        <div class="bg-ir-carbon border border-ir-copper rounded-md p-5">
            <span class="text-xs font-semibold text-ir-bone/70 uppercase tracking-wider">Net Parts Profit</span>
            <h3 class="text-2xl font-extrabold text-ir-gold mt-1">₱{{ number_format($totalPartsProfit, 2) }}</h3>
            <span class="text-xs text-ir-gold/80 mt-1 inline-block font-mono">Retail sales minus cost</span>
        </div>
        @endif

        <div class="bg-ir-carbon border border-ir-copper rounded-md p-5">
            <span class="text-xs font-semibold text-ir-bone/70 uppercase tracking-wider">Total Completed Jobs</span>
            <h3 class="text-2xl font-extrabold text-ir-bone mt-1">{{ $totalCompletedJobs }} Tickets</h3>
            <span class="text-xs text-ir-bone/70 mt-1 inline-block font-mono">Successfully repaired</span>
        </div>

        @if($outstandingBalance !== null)
        <div class="bg-ir-carbon border border-ir-copper rounded-md p-5">
            <span class="text-xs font-semibold text-ir-bone/70 uppercase tracking-wider">Outstanding Invoices</span>
            <h3 class="text-2xl font-extrabold text-red-400 mt-1">₱{{ number_format($outstandingBalance, 2) }}</h3>
            <span class="text-xs text-red-400/80 mt-1 inline-block font-mono">Pending customer payments</span>
        </div>
        @endif
    </div>

    @if(!empty($incomeForecast['labels']) || !empty($inventoryForecast))
    <!-- Forecasting & Analytics Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        @if(!empty($incomeForecast['labels']))
        <!-- Monthly Income Trend Forecast Chart -->
        <div class="bg-ir-carbon border border-ir-copper rounded-md p-6 space-y-4">
            <h4 class="text-sm font-bold text-ir-bone uppercase tracking-wider flex items-center justify-between border-b border-ir-copper pb-3">
                <span><i class="fa-solid fa-chart-line text-ir-gold mr-1"></i> Revenue Moving Average Forecast</span>
                <span class="text-xs text-ir-gold font-mono">3-Month Moving Avg</span>
            </h4>

            <div class="relative h-64 w-full">
                <canvas id="forecastChart"></canvas>
            </div>
        </div>
        @endif

        @if(!empty($inventoryForecast))
        <!-- Inventory Demand Forecasting (Moving Average) -->
        <div class="bg-ir-carbon border border-ir-copper rounded-md p-6 space-y-4">
            <h4 class="text-sm font-bold text-ir-bone uppercase tracking-wider border-b border-ir-copper pb-3 flex items-center justify-between">
                <span><i class="fa-solid fa-boxes-packing text-ir-gold mr-1"></i> Parts Stock Demand Forecasting</span>
                <span class="text-xs text-amber-400 font-bold">Trend-Based Reorder Suggestions</span>
            </h4>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-ir-bone">
                    <thead class="bg-ir-void font-semibold text-ir-bone/70 uppercase border-b border-ir-copper">
                        <tr>
                            <th class="px-3 py-2">Part Name</th>
                            <th class="px-3 py-2 text-center">Stock</th>
                            <th class="px-3 py-2 text-center">30D Usage</th>
                            <th class="px-3 py-2 text-center">Projected Demand</th>
                            <th class="px-3 py-2 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ir-copper">
                        @foreach(array_slice($inventoryForecast, 0, 6) as $f)
                            <tr class="hover:bg-ir-carbon/40">
                                <td class="px-3 py-2 font-medium text-ir-bone">
                                    {{ $f['name'] }}
                                    <span class="block text-[10px] text-ir-copper">SKU: {{ $f['sku'] }}</span>
                                </td>
                                <td class="px-3 py-2 text-center font-bold {{ $f['needs_reorder'] ? 'text-red-400' : 'text-ir-bone' }}">
                                    {{ $f['current_stock'] }}
                                </td>
                                <td class="px-3 py-2 text-center">{{ $f['units_used_30_days'] }}</td>
                                <td class="px-3 py-2 text-center font-bold text-ir-gold">{{ $f['projected_30day_demand'] }} units</td>
                                <td class="px-3 py-2 text-center">
                                    @if($f['needs_reorder'])
                                        <span class="px-2 py-0.5 rounded bg-red-500/10 text-red-400 border border-red-500/20 text-[10px] font-bold">
                                            Reorder +{{ $f['suggested_reorder_qty'] }}
                                        </span>
                                    @else
                                        <span class="text-[10px] text-emerald-400">Stock Healthy</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    </div>
    @endif

    <!-- Technician Performance & Best Selling Parts Tables -->
    <div class="grid grid-cols-1 {{ $bestSellingParts->isNotEmpty() ? 'lg:grid-cols-2' : '' }} gap-6">

        <!-- Technician Performance Report -->
        <div class="bg-ir-carbon border border-ir-copper rounded-md p-6 space-y-4">
            <h4 class="text-sm font-bold text-ir-bone uppercase tracking-wider border-b border-ir-copper pb-3">
                <i class="fa-solid fa-user-gear text-ir-gold mr-1"></i> Technician Performance Summary
            </h4>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-ir-bone">
                    <thead class="bg-ir-void font-semibold text-ir-bone/70 uppercase border-b border-ir-copper">
                        <tr>
                            <th class="px-4 py-3">Technician</th>
                            <th class="px-4 py-3">Specialty</th>
                            <th class="px-4 py-3 text-center">Completed Jobs</th>
                            <th class="px-4 py-3 text-right">Rating Score</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ir-copper">
                        @foreach($techPerformance as $tech)
                            <tr class="hover:bg-ir-carbon/40">
                                <td class="px-4 py-3 font-bold text-ir-bone">{{ $tech->name }}</td>
                                <td class="px-4 py-3 text-ir-bone/70">{{ $tech->specialty }}</td>
                                <td class="px-4 py-3 text-center font-bold text-ir-gold">{{ $tech->period_completed_count }}</td>
                                <td class="px-4 py-3 text-right text-amber-400 font-bold">★ {{ number_format($tech->rating, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if($bestSellingParts->isNotEmpty())
        <!-- Best-Selling Parts Report -->
        <div class="bg-ir-carbon border border-ir-copper rounded-md p-6 space-y-4">
            <h4 class="text-sm font-bold text-ir-bone uppercase tracking-wider border-b border-ir-copper pb-3">
                <i class="fa-solid fa-award text-ir-gold mr-1"></i> Best-Selling Replacement Parts
            </h4>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-ir-bone">
                    <thead class="bg-ir-void font-semibold text-ir-bone/70 uppercase border-b border-ir-copper">
                        <tr>
                            <th class="px-4 py-3">Part Name</th>
                            <th class="px-4 py-3 text-center">Units Consumed</th>
                            <th class="px-4 py-3 text-right">Total Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ir-copper">
                        @forelse($bestSellingParts as $bp)
                            <tr class="hover:bg-ir-carbon/40">
                                <td class="px-4 py-3 font-medium text-ir-bone">{{ $bp->part?->name }}</td>
                                <td class="px-4 py-3 text-center font-bold text-emerald-400">{{ $bp->total_used }} units</td>
                                <td class="px-4 py-3 text-right font-bold text-ir-bone">₱{{ number_format($bp->total_revenue, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-6 text-center text-ir-copper">No parts usage recorded for period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    </div>

</div>

@if(!empty($incomeForecast['labels']))
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const canvas = document.getElementById('forecastChart');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        const data = @json($incomeForecast);

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Monthly Revenue (₱)',
                    data: data.historical.concat([data.projected_next_month]),
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    fill: true,
                    tension: 0.3,
                    borderWidth: 3,
                    pointRadius: 5,
                    pointBackgroundColor: '#818cf8',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color: 'rgba(51, 65, 85, 0.3)' }, ticks: { color: '#94a3b8' } },
                    y: { grid: { color: 'rgba(51, 65, 85, 0.3)' }, ticks: { color: '#94a3b8' } }
                }
            }
        });
    });
</script>
@endpush
@endif
@endsection
