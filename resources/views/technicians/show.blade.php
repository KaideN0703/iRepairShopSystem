@extends('layouts.app')

@section('title', 'Technician: ' . $technician->name)

@section('content')
<div class="space-y-6">

    <div class="bg-ir-carbon border border-ir-copper rounded-md p-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-md bg-ir-gold/20 border border-ir-gold/30 text-ir-gold font-bold text-xl flex items-center justify-center">
                {{ strtoupper(substr($technician->name, 0, 2)) }}
            </div>
            <div>
                <h2 class="text-2xl font-bold text-ir-bone">{{ $technician->name }}</h2>
                <p class="text-xs text-ir-bone/70 mt-0.5">Specialty: {{ $technician->specialty }} | Phone: {{ $technician->phone }} | Rating: ★ {{ number_format($technician->rating, 2) }}</p>
            </div>
        </div>

        <div class="text-right bg-ir-void px-5 py-3 rounded-md border border-ir-copper">
            <span class="block text-xs font-semibold text-ir-bone/70 uppercase">Avg. Turnaround Time</span>
            <span class="text-lg font-bold text-emerald-400">{{ round($avgHours, 1) }} Hours</span>
        </div>
    </div>

    <!-- Active Assigned Jobs -->
    <div class="bg-ir-carbon border border-ir-copper rounded-md p-6 space-y-4">
        <h4 class="text-xs font-bold text-ir-bone/70 uppercase tracking-wider border-b border-ir-copper pb-3 flex items-center justify-between">
            <span><i class="fa-solid fa-screwdriver-wrench text-ir-gold mr-1"></i> Active Assigned Jobs Queue ({{ $activeJobs->count() }})</span>
        </h4>

        <div class="space-y-3">
            @forelse($activeJobs as $job)
                <div class="p-4 rounded-md bg-ir-void border border-ir-copper flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('job_orders.show', $job) }}" class="font-bold text-ir-gold hover:underline">#{{ $job->ticket_number }}</a>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-ir-amber-deep/10 text-ir-gold border border-ir-gold/30">{{ $job->status }}</span>
                        </div>
                        <span class="block text-xs font-semibold text-ir-bone mt-1">{{ $job->customer?->name }} - {{ $job->device?->brand }} {{ $job->device?->model }}</span>
                        <p class="text-xs text-ir-bone/70 mt-0.5">{{ $job->reported_issue }}</p>
                    </div>

                    <a href="{{ route('job_orders.show', $job) }}" class="px-4 py-2 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone text-xs font-semibold">
                        Work on Ticket →
                    </a>
                </div>
            @empty
                <div class="text-center py-6 text-ir-copper text-xs">No active repair jobs assigned currently.</div>
            @endforelse
        </div>
    </div>

</div>
@endsection
