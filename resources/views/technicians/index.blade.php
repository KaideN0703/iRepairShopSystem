@extends('layouts.app')

@section('title', 'Technicians & Workload Monitor')

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between bg-ir-carbon border border-ir-copper p-5 rounded-md">
        <div>
            <h2 class="text-xl font-bold text-ir-bone">Technician Roster & Workload Monitoring</h2>
            <p class="text-xs text-ir-bone/70 mt-1">Smart job assignment and workload balancing for repair staff</p>
        </div>

        <a href="{{ route('technicians.create') }}" class="px-5 py-2.5 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone font-semibold text-sm flex items-center gap-2 transition-colors">
            <i class="fa-solid fa-plus"></i> Add Technician &amp; Staff
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @forelse($technicians as $tech)
            <div class="bg-ir-carbon border border-ir-copper rounded-md p-6 space-y-4 flex flex-col justify-between">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-md bg-ir-gold/20 border border-ir-gold/30 flex items-center justify-center font-bold text-ir-gold text-lg">
                                {{ strtoupper(substr($tech->name, 0, 2)) }}
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-ir-bone">{{ $tech->name }}</h3>
                                <span class="text-xs text-ir-gold font-medium">{{ $tech->specialty }}</span>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 rounded-full bg-ir-carbon text-xs font-bold text-amber-400 border border-ir-copper">
                            ★ {{ number_format($tech->rating, 2) }}
                        </span>
                    </div>

                    <div class="p-3 rounded-md bg-ir-void border border-ir-copper grid grid-cols-2 gap-2 text-center text-xs">
                        <div>
                            <span class="block text-ir-bone/70">Active Workload</span>
                            <strong class="block text-lg font-bold text-ir-gold mt-0.5">{{ $tech->active_jobs_count }} Jobs</strong>
                        </div>
                        <div>
                            <span class="block text-ir-bone/70">Completed Jobs</span>
                            <strong class="block text-lg font-bold text-emerald-400 mt-0.5">{{ $tech->completed_jobs_count }} Jobs</strong>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-ir-copper flex justify-between items-center">
                    <span class="text-xs text-ir-bone/70"><i class="fa-solid fa-phone text-ir-copper mr-1"></i> {{ $tech->phone ?? 'No phone' }}</span>
                    <a href="{{ route('technicians.show', $tech) }}" class="px-4 py-2 rounded-md bg-ir-carbon hover:bg-ir-carbon text-ir-bone text-xs font-semibold">
                        View Assigned Jobs →
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-3 text-center py-10 text-ir-copper">No technicians registered.</div>
        @endforelse
    </div>

</div>
@endsection
