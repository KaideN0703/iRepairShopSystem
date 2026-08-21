@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div style="display:flex; flex-direction:column; gap:1.25rem;">

    {{-- ================================================================
         STAT CARDS
         ================================================================ --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:1rem;">

        {{-- Pending Intake --}}
        <a href="{{ route('job_orders.index', ['status' => 'Received']) }}" class="ir-stat-card" style="text-decoration:none; display:block; transition:border-color 150ms;" onmouseover="this.style.borderColor='#F5A623'" onmouseout="this.style.borderColor='#7A4A12'">
            <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:0.5rem;">
                <div>
                    <div class="ir-stat-label">Pending Intake</div>
                    <div class="ir-stat-value">{{ $pendingCount }}</div>
                    <div style="font-size:0.7rem; color:#B97A1A; margin-top:0.35rem; font-family:'Inter',sans-serif;">
                        <i class="fa-solid fa-clock" style="margin-right:3px;"></i>Received &amp; Diagnosing
                    </div>
                </div>
                <div style="width:38px; height:38px; border-radius:5px; background:rgba(245,166,35,0.1); border:1px solid rgba(245,166,35,0.25); display:flex; align-items:center; justify-content:center; color:#F5A623; flex-shrink:0;">
                    <i class="fa-solid fa-hourglass-start"></i>
                </div>
            </div>
        </a>

        {{-- Ongoing Repairs --}}
        <a href="{{ route('job_orders.index', ['status' => 'Under Repair']) }}" class="ir-stat-card" style="text-decoration:none; display:block; transition:border-color 150ms;" onmouseover="this.style.borderColor='#F5A623'" onmouseout="this.style.borderColor='#7A4A12'">
            <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:0.5rem;">
                <div>
                    <div class="ir-stat-label">Ongoing Repairs</div>
                    <div class="ir-stat-value">{{ $ongoingCount }}</div>
                    <div style="font-size:0.7rem; color:#B97A1A; margin-top:0.35rem; font-family:'Inter',sans-serif;">
                        <i class="fa-solid fa-screwdriver-wrench" style="margin-right:3px;"></i>Under Repair &amp; Testing
                    </div>
                </div>
                <div style="width:38px; height:38px; border-radius:5px; background:rgba(245,166,35,0.1); border:1px solid rgba(245,166,35,0.25); display:flex; align-items:center; justify-content:center; color:#F5A623; flex-shrink:0;">
                    <i class="fa-solid fa-screwdriver-wrench"></i>
                </div>
            </div>
        </a>

        {{-- Ready / Completed --}}
        <a href="{{ route('job_orders.index', ['status' => 'Ready for Pickup']) }}" class="ir-stat-card" style="text-decoration:none; display:block; transition:border-color 150ms;" onmouseover="this.style.borderColor='#35D07F'" onmouseout="this.style.borderColor='#7A4A12'">
            <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:0.5rem;">
                <div>
                    <div class="ir-stat-label">Ready / Completed</div>
                    <div class="ir-stat-value">{{ $completedCount }}</div>
                    <div style="font-size:0.7rem; color:#35D07F; margin-top:0.35rem; font-family:'Inter',sans-serif;">
                        <i class="fa-solid fa-circle-check" style="margin-right:3px;"></i>Ready for Pickup
                    </div>
                </div>
                <div style="width:38px; height:38px; border-radius:5px; background:rgba(53,208,127,0.08); border:1px solid rgba(53,208,127,0.2); display:flex; align-items:center; justify-content:center; color:#35D07F; flex-shrink:0;">
                    <i class="fa-solid fa-box-check"></i>
                </div>
            </div>
        </a>

        @if($totalRevenueMonth !== null)
        {{-- Monthly Revenue --}}
        <a href="{{ route('invoices.index', ['status' => 'paid']) }}" class="ir-stat-card" style="text-decoration:none; display:block; transition:border-color 150ms;" onmouseover="this.style.borderColor='#F5A623'" onmouseout="this.style.borderColor='#7A4A12'">
            <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:0.5rem;">
                <div>
                    <div class="ir-stat-label">Monthly Revenue</div>
                    <div class="ir-stat-value" style="font-size:1.5rem;">₱{{ number_format($totalRevenueMonth ?? 0, 2) }}</div>
                    <div style="font-size:0.7rem; color:#B97A1A; margin-top:0.35rem; font-family:'Inter',sans-serif;">
                        <i class="fa-solid fa-handshake-angle" style="margin-right:3px;"></i>{{ $claimedCount }} Claimed / Released
                    </div>
                </div>
                <div style="width:38px; height:38px; border-radius:5px; background:rgba(245,166,35,0.1); border:1px solid rgba(245,166,35,0.25); display:flex; align-items:center; justify-content:center; color:#F5A623; flex-shrink:0;">
                    <i class="fa-solid fa-sack-dollar"></i>
                </div>
            </div>
        </a>
        @endif

    </div>

    {{-- ================================================================
         REPAIR PIPELINE — CIRCUIT STAGE BREAKDOWN
         ================================================================ --}}
    <div class="ir-card">
        <div class="ir-card-header">
            <h4 class="ir-card-title">
                <i class="fa-solid fa-diagram-project" style="color:#F5A623; margin-right:0.4rem;"></i>
                Repair Lifecycle Pipeline
            </h4>
        </div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(90px,1fr)); gap:0.5rem;">
            @php
                $pipelineOrder = ['Received','Diagnosing','Waiting for Parts','Under Repair','Testing','Ready for Pickup','Completed','Released'];
            @endphp
            @foreach($pipelineOrder as $idx => $stage)
                @php $count = $statusCounts[$stage] ?? 0; @endphp
                <a href="{{ route('job_orders.index', ['status' => $stage]) }}"
                   style="
                       display:block;
                       background:#0B0B0C;
                       border:1px solid #7A4A12;
                       border-radius:5px;
                       padding:0.7rem 0.5rem;
                       text-align:center;
                       text-decoration:none;
                       transition:border-color 150ms, background 150ms;
                       position:relative;
                   "
                   onmouseover="this.style.borderColor='#F5A623';this.style.background='rgba(245,166,35,0.05)'"
                   onmouseout="this.style.borderColor='#7A4A12';this.style.background='#0B0B0C'"
                >
                    {{-- Circuit node dot --}}
                    <div style="width:8px; height:8px; border-radius:50%; background:{{ $count > 0 ? '#F5A623' : '#7A4A12' }}; margin:0 auto 0.4rem; box-shadow:{{ $count > 0 ? '0 0 6px rgba(245,166,35,0.5)' : 'none' }};"></div>
                    <span style="display:block; font-family:'JetBrains Mono',monospace; font-size:1.4rem; font-weight:700; color:{{ $count > 0 ? '#F5A623' : '#9a8f7e' }}; line-height:1;">{{ $count }}</span>
                    <span style="display:block; font-family:'Inter',sans-serif; font-size:0.62rem; color:#9a8f7e; margin-top:0.3rem; line-height:1.3; text-transform:uppercase; letter-spacing:0.04em;">{{ $stage }}</span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- ================================================================
         CHARTS + LOW STOCK — DYNAMIC GRID
         ================================================================ --}}
    @php
        $hasIncomeForecast = !empty($incomeForecast['labels']);
    @endphp

    <div class="grid grid-cols-1 {{ $hasIncomeForecast ? 'lg:grid-cols-3' : '' }} gap-4">

        @if($hasIncomeForecast)
        {{-- Income Chart --}}
        <div class="ir-card lg:col-span-2">
            <div class="ir-card-header">
                <div>
                    <h4 class="ir-card-title">
                        <i class="fa-solid fa-chart-column" style="color:#F5A623; margin-right:0.4rem;"></i>
                        Income Overview &amp; Moving Average Trend
                    </h4>
                    <p style="font-size:0.72rem; color:#9a8f7e; margin:0.2rem 0 0; font-family:'Inter',sans-serif;">
                        Historical revenue with 3-month moving average projection
                    </p>
                </div>
                <span style="
                    padding:0.25rem 0.65rem;
                    border-radius:4px;
                    background:rgba(245,166,35,0.08);
                    border:1px solid rgba(245,166,35,0.25);
                    color:#F5A623;
                    font-family:'JetBrains Mono',monospace;
                    font-size:0.72rem;
                    white-space:nowrap;
                    flex-shrink:0;
                ">
                    Proj: ₱{{ number_format($incomeForecast['projected_next_month'] ?? 0, 0) }}
                </span>
            </div>
            <div style="position:relative; height:220px; width:100%;">
                <canvas id="incomeChart"></canvas>
            </div>
        </div>
        @endif

        {{-- Low Stock Alerts --}}
        <div class="ir-card {{ !$hasIncomeForecast ? 'w-full' : '' }}">
            <div class="ir-card-header">
                <h4 class="ir-card-title">
                    <i class="fa-solid fa-triangle-exclamation" style="color:#F5A623; margin-right:0.4rem;"></i>
                    Low-Stock Alerts
                </h4>
                <a href="{{ route('inventory.index', ['low_stock' => 1]) }}"
                   style="font-size:0.72rem; color:#B97A1A; text-decoration:none; font-family:'Inter',sans-serif; white-space:nowrap;"
                   onmouseover="this.style.color='#F5A623'"
                   onmouseout="this.style.color='#B97A1A'"
                >View All</a>
            </div>

            @if($lowStockParts->isEmpty())
                <div style="text-align:center; padding:2rem 0; color:#9a8f7e;">
                    <i class="fa-solid fa-box-open" style="font-size:1.5rem; display:block; margin-bottom:0.5rem; color:#7A4A12;"></i>
                    <p style="font-size:0.8rem; margin:0; font-family:'Inter',sans-serif;">All inventory parts are healthy</p>
                </div>
            @else
                <div style="display:flex; flex-direction:column; gap:0.5rem; max-height:220px; overflow-y:auto; padding-right:2px;">
                    @foreach($lowStockParts as $part)
                        <a href="{{ route('inventory.show', $part) }}"
                           style="background:#0B0B0C; border:1px solid rgba(229,72,77,0.2); border-radius:4px; padding:0.6rem 0.75rem; display:flex; align-items:center; justify-content:space-between; gap:0.5rem; text-decoration:none; transition:border-color 150ms;"
                           onmouseover="this.style.borderColor='rgba(229,72,77,0.6)'"
                           onmouseout="this.style.borderColor='rgba(229,72,77,0.2)'">
                            <div style="min-width:0;">
                                <span style="display:block; font-size:0.78rem; font-weight:600; color:#EDE6D6; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-family:'Inter',sans-serif;">{{ $part->name }}</span>
                                <span style="display:block; font-size:0.65rem; color:#9a8f7e; font-family:'JetBrains Mono',monospace;">{{ $part->sku }} · {{ $part->location_rack ?? 'N/A' }}</span>
                            </div>
                            <div style="text-align:right; flex-shrink:0;">
                                <x-stock-badge :part="$part" :show-reorder="true" />
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif

            @canany(['suppliers.view', 'suppliers.manage'])
            <div style="margin-top:1rem; padding-top:0.75rem; border-top:1px solid rgba(122,74,18,0.35);">
                <a href="{{ route('suppliers.index') }}" class="btn-secondary btn-sm" style="width:100%; justify-content:center;">
                    <i class="fa-solid fa-truck-field"></i> Restock via Suppliers
                </a>
            </div>
            @endcanany
        </div>

    </div>

    {{-- ================================================================
         RECENT ACTIVITY FEED
         ================================================================ --}}
    <div class="ir-card">
        <div class="ir-card-header">
            <h4 class="ir-card-title">
                <i class="fa-solid fa-clock-rotate-left" style="color:#F5A623; margin-right:0.4rem;"></i>
                Recent Repair Activities
            </h4>
            <a href="{{ route('job_orders.index') }}"
               style="font-size:0.72rem; color:#B97A1A; text-decoration:none; font-family:'Inter',sans-serif; white-space:nowrap;"
               onmouseover="this.style.color='#F5A623'"
               onmouseout="this.style.color='#B97A1A'"
            >View All Job Orders</a>
        </div>

        <div>
            @forelse($recentActivities as $act)
                <div style="padding:0.65rem 0; display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; border-bottom:1px solid rgba(122,74,18,0.3);">
                    <div style="display:flex; align-items:flex-start; gap:0.75rem;">
                        <div style="width:32px; height:32px; border-radius:4px; background:rgba(245,166,35,0.08); border:1px solid rgba(245,166,35,0.2); display:flex; align-items:center; justify-content:center; color:#F5A623; font-size:0.75rem; flex-shrink:0; margin-top:1px;">
                            <i class="fa-solid fa-ticket"></i>
                        </div>
                        <div>
                            <div style="font-size:0.85rem; font-family:'Inter',sans-serif;">
                                <a href="{{ route('job_orders.show', $act->job_order_id) }}"
                                   style="font-weight:600; color:#EDE6D6; text-decoration:none; font-family:'JetBrains Mono',monospace; font-size:0.8rem;"
                                   onmouseover="this.style.color='#F5A623'"
                                   onmouseout="this.style.color='#EDE6D6'"
                                >#{{ $act->jobOrder?->ticket_number }}</a>
                                <span style="color:#9a8f7e;"> — {{ $act->jobOrder?->customer?->name }}
                                ({{ $act->jobOrder?->device?->brand }} {{ $act->jobOrder?->device?->model }})</span>
                            </div>
                            <div style="font-size:0.75rem; color:#9a8f7e; margin-top:2px; font-family:'Inter',sans-serif;">
                                Status → <span style="color:#F5A623; font-weight:500;">{{ $act->status_to }}</span>: {{ $act->remarks }}
                            </div>
                        </div>
                    </div>
                    <div style="text-align:right; flex-shrink:0;">
                        <span style="font-size:0.72rem; color:#9a8f7e; font-family:'JetBrains Mono',monospace; display:block;">{{ $act->created_at->diffForHumans() }}</span>
                        <span style="font-size:0.68rem; color:#7A4A12; font-family:'Inter',sans-serif;">{{ $act->user?->name ?? 'System' }}</span>
                    </div>
                </div>
            @empty
                <div style="padding:2rem 0; text-align:center; color:#9a8f7e; font-size:0.85rem; font-family:'Inter',sans-serif;">
                    No recent activity recorded yet.
                </div>
            @endforelse
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const canvas = document.getElementById('incomeChart');
        const forecastData = @json($incomeForecast);
        if (!canvas || !forecastData || !forecastData.historical || !forecastData.historical.length) {
            return;
        }
        const ctx = canvas.getContext('2d');

        // Brand gold palette for the chart
        const goldBars   = 'rgba(245, 166, 35, 0.55)';
        const goldBorder = '#F5A623';
        const projBar    = 'rgba(185, 122, 26, 0.7)';
        const projBorder = '#B97A1A';

        const barColors   = forecastData.historical.map(() => goldBars).concat([projBar]);
        const borderColors = forecastData.historical.map(() => goldBorder).concat([projBorder]);

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: forecastData.labels,
                datasets: [{
                    label: 'Revenue (₱)',
                    data: forecastData.historical.concat([forecastData.projected_next_month]),
                    backgroundColor: barColors,
                    borderColor: borderColors,
                    borderWidth: 1.5,
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#17181A',
                        borderColor: '#7A4A12',
                        borderWidth: 1,
                        titleColor: '#F5A623',
                        bodyColor: '#EDE6D6',
                        titleFont: { family: 'JetBrains Mono', size: 11 },
                        bodyFont:  { family: 'Inter', size: 12 },
                        callbacks: {
                            label: ctx => ' ₱' + ctx.raw.toLocaleString('en-PH', {minimumFractionDigits:2})
                        }
                    }
                },
                scales: {
                    x: {
                        grid:  { color: 'rgba(122,74,18,0.2)' },
                        ticks: { color: '#9a8f7e', font: { family: 'Inter', size: 11 } }
                    },
                    y: {
                        grid:  { color: 'rgba(122,74,18,0.2)' },
                        ticks: { color: '#9a8f7e', font: { family: 'JetBrains Mono', size: 11 },
                                 callback: v => '₱' + v.toLocaleString() }
                    }
                }
            }
        });
    });
</script>
@endpush
