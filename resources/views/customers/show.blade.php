@extends('layouts.app')

@section('title', 'Customer: ' . $customer->name)

@section('content')
<div class="space-y-6">

    <!-- Header profile -->
    <div class="bg-ir-carbon border border-ir-copper rounded-md p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-md bg-ir-gold/20 border border-ir-gold/30 text-ir-gold font-bold text-xl flex items-center justify-center">
                {{ strtoupper(substr($customer->name, 0, 2)) }}
            </div>
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="text-2xl font-bold text-ir-bone">{{ $customer->name }}</h2>
                    <span class="px-3 py-1 rounded-full bg-ir-carbon border border-ir-copper text-xs font-mono text-ir-gold">{{ $customer->customer_code }}</span>
                </div>
                <p class="text-xs text-ir-bone/70 mt-1">
                    <i class="fa-solid fa-phone text-ir-copper mr-1"></i> {{ $customer->phone }} | 
                    <i class="fa-solid fa-envelope text-ir-copper mr-1"></i> {{ $customer->email ?? 'No email' }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            @canany(['devices.create', 'customers.manage', 'jobs.create'])
            <a href="{{ route('devices.create', ['customer_id' => $customer->id]) }}" class="px-4 py-2 rounded-md bg-ir-carbon hover:bg-ir-carbon text-ir-bone text-xs font-semibold">
                + Register Device
            </a>
            @endcanany
            @can('jobs.create')
            <a href="{{ route('job_orders.create', ['customer_id' => $customer->id]) }}" class="px-4 py-2 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone text-xs font-semibold transition-colors">
                + New Repair Ticket
            </a>
            @endcan
        </div>
    </div>

    <!-- Linked Devices & Repair History -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Devices List -->
        <div class="bg-ir-carbon border border-ir-copper rounded-md p-6 space-y-4">
            <h4 class="text-xs font-bold text-ir-bone/70 uppercase tracking-wider flex items-center justify-between border-b border-ir-copper pb-3">
                <span><i class="fa-solid fa-mobile-screen text-ir-gold mr-1"></i> Registered Devices ({{ $customer->devices->count() }})</span>
            </h4>

            <div class="space-y-3">
                @forelse($customer->devices as $dev)
                    <div class="p-4 rounded-md bg-ir-void border border-ir-copper space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-sm text-ir-bone">{{ $dev->brand }} {{ $dev->model }}</span>
                            <span class="text-[10px] text-ir-bone/70 font-mono">{{ $dev->device_type }}</span>
                        </div>
                        <p class="text-xs text-ir-bone/70">S/N: {{ $dev->serial_number ?? 'N/A' }} | Color: {{ $dev->color ?? 'N/A' }}</p>
                        <div class="pt-2 border-t border-ir-copper/80 flex justify-between items-center text-xs">
                            <span class="text-ir-gold">Passcode: {{ $dev->passcode_pattern ?? 'None' }}</span>
                            @can('jobs.create')
                            <a href="{{ route('job_orders.create', ['customer_id' => $customer->id]) }}" class="text-ir-bone hover:text-ir-bone hover:underline">Repair Device →</a>
                            @endcan
                        </div>
                    </div>
                @empty
                    <div class="text-center py-6 text-ir-copper text-xs">No devices linked to this customer yet.</div>
                @endforelse
            </div>
        </div>

        <!-- Repair Job History (2 cols) -->
        <div class="lg:col-span-2 bg-ir-carbon border border-ir-copper rounded-md p-6 space-y-4">
            <h4 class="text-xs font-bold text-ir-bone/70 uppercase tracking-wider border-b border-ir-copper pb-3 flex items-center justify-between">
                <span><i class="fa-solid fa-ticket text-ir-gold mr-1"></i> Repair Job Order History ({{ $customer->jobOrders->count() }})</span>
            </h4>

            <div class="space-y-3">
                @forelse($customer->jobOrders as $job)
                    <div class="p-4 rounded-md bg-ir-void border border-ir-copper flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('job_orders.show', $job) }}" class="font-bold text-ir-gold hover:underline">#{{ $job->ticket_number }}</a>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-ir-carbon border border-ir-copper text-ir-bone">{{ $job->status }}</span>
                            </div>
                            <span class="block text-xs font-semibold text-ir-bone mt-1">{{ $job->device?->brand }} {{ $job->device?->model }}</span>
                            <p class="text-xs text-ir-bone/70 mt-0.5">{{ $job->reported_issue }}</p>
                        </div>

                        <div class="text-right sm:text-right w-full sm:w-auto flex sm:flex-col justify-between sm:justify-center items-center sm:items-end">
                            <span class="text-sm font-bold text-ir-bone">₱{{ number_format($job->total_cost, 2) }}</span>
                            <span class="text-[10px] text-ir-copper">{{ $job->created_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-ir-copper text-xs">No repair history recorded.</div>
                @endforelse
            </div>
        </div>

    </div>

</div>
@endsection
