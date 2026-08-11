@extends('layouts.app')

@section('title', 'Device: ' . $device->brand . ' ' . $device->model)

@section('content')
<div class="space-y-6">

    <div class="bg-ir-carbon border border-ir-copper rounded-md p-6 flex items-center justify-between">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="text-2xl font-bold text-ir-bone">{{ $device->brand }} {{ $device->model }}</h2>
                <span class="px-3 py-1 rounded-full text-xs font-mono font-bold bg-ir-carbon text-ir-gold border border-ir-copper">{{ $device->device_type }}</span>
            </div>
            <p class="text-xs text-ir-bone/70 mt-1">Owner: <strong>{{ $device->customer?->name }}</strong> | Serial: {{ $device->serial_number ?? 'N/A' }} | Passcode: <span class="font-mono text-amber-400">{{ $device->passcode_pattern ?? 'None' }}</span></p>
        </div>

        <div class="flex items-center gap-3">
            @can('jobs.create')
            <a href="{{ route('job_orders.create', ['customer_id' => $device->customer_id]) }}" class="px-4 py-2 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone text-xs font-semibold transition-colors">
                + Create Repair Ticket
            </a>
            @endcan
        </div>
    </div>

    <!-- Related Job Orders -->
    <div class="bg-ir-carbon border border-ir-copper rounded-md p-6 space-y-4">
        <h4 class="text-xs font-bold text-ir-bone/70 uppercase tracking-wider border-b border-ir-copper pb-3">
            Repair History for this Device ({{ $device->jobOrders->count() }})
        </h4>

        <div class="space-y-3">
            @forelse($device->jobOrders as $job)
                <div class="p-4 rounded-md bg-ir-void border border-ir-copper flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('job_orders.show', $job) }}" class="font-bold text-ir-gold hover:underline">#{{ $job->ticket_number }}</a>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-ir-carbon text-ir-bone">{{ $job->status }}</span>
                        </div>
                        <p class="text-xs text-ir-bone mt-1">{{ $job->reported_issue }}</p>
                    </div>

                    <span class="text-xs font-bold text-ir-bone">₱{{ number_format($job->total_cost, 2) }}</span>
                </div>
            @empty
                <div class="text-center py-6 text-ir-copper text-xs">No repair job orders logged for this device yet.</div>
            @endforelse
        </div>
    </div>

</div>
@endsection
