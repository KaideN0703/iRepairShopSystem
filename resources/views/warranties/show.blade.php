@extends('layouts.app')

@section('title', 'Warranty Record')

@section('content')
<div class="space-y-6" x-data="{ openClaimModal: false }">

    <div class="bg-ir-carbon border border-ir-copper rounded-md p-6 shadow-lg flex items-center justify-between">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="text-2xl font-bold text-ir-bone">Warranty Record</h2>
                <span class="px-3 py-1 rounded-full text-xs font-bold border uppercase
                    @if($warranty->status === 'active') bg-emerald-500/10 text-emerald-400 border-emerald-500/30
                    @elseif($warranty->status === 'claimed') bg-amber-500/10 text-amber-400 border-amber-500/30
                    @else bg-ir-carbon text-ir-bone/70 border-ir-copper @endif">
                    {{ $warranty->status }}
                </span>
            </div>
            <p class="text-xs text-ir-bone/70 mt-1">
                Customer: <strong>{{ $warranty->customer?->name }}</strong> | Device: {{ $warranty->device?->brand }} {{ $warranty->device?->model }} | Ticket: #{{ $warranty->jobOrder?->ticket_number }}
            </p>
        </div>

        <button @click="openClaimModal = true" class="px-5 py-2.5 rounded-md bg-amber-600 hover:bg-amber-500 text-ir-bone text-xs font-bold shadow-lg shadow-amber-600/30">
            <i class="fa-solid fa-file-circle-exclamation mr-1"></i> File Warranty Claim
        </button>
    </div>

    <!-- Warranty Policy Card -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-ir-carbon border border-ir-copper rounded-md p-5 shadow-lg space-y-2">
            <span class="text-xs font-bold text-ir-bone/70 uppercase">Coverage Period</span>
            <h3 class="text-lg font-bold text-ir-bone">{{ $warranty->warranty_period_days }} Days Policy</h3>
            <p class="text-xs text-ir-bone/70">Start: {{ $warranty->start_date?->format('M d, Y') }} | End: {{ $warranty->end_date?->format('M d, Y') }}</p>
        </div>

        <div class="md:col-span-2 bg-ir-carbon border border-ir-copper rounded-md p-5 shadow-lg space-y-2">
            <span class="text-xs font-bold text-ir-bone/70 uppercase">Coverage Details</span>
            <p class="text-xs text-ir-bone">{{ $warranty->coverage_details ?? 'Standard 90-Day Parts & Labor Warranty coverage against manufacturing or installation defects.' }}</p>
        </div>
    </div>

    <!-- Claim Records Table -->
    <div class="bg-ir-carbon border border-ir-copper rounded-md p-6 shadow-lg space-y-4">
        <h4 class="text-xs font-bold text-ir-bone/70 uppercase tracking-wider border-b border-ir-copper pb-3">
            Warranty Claim Log ({{ $warranty->claims->count() }})
        </h4>

        <div class="space-y-3">
            @forelse($warranty->claims as $claim)
                <div class="p-4 rounded-md bg-ir-void border border-ir-copper space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-amber-400 font-mono text-sm">#{{ $claim->claim_number }}</span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-ir-carbon text-ir-bone">
                            Status: {{ $claim->resolution_status }}
                        </span>
                    </div>
                    <p class="text-xs text-ir-bone">Issue: {{ $claim->issue_description }}</p>
                    <p class="text-[11px] text-ir-copper">Filed on: {{ $claim->claim_date?->format('M d, Y') }}</p>
                </div>
            @empty
                <div class="text-center py-6 text-ir-copper text-xs">No claims filed against this warranty.</div>
            @endforelse
        </div>
    </div>

    <!-- File Claim Modal -->
    <div x-show="openClaimModal" class="fixed inset-0 z-50 bg-ir-void/80 backdrop-blur-sm flex items-center justify-center p-4" x-cloak>
        <div class="bg-ir-carbon border border-ir-copper rounded-md p-6 max-w-md w-full space-y-4 shadow-2xl">
            <div class="flex items-center justify-between border-b border-ir-copper pb-3">
                <h3 class="text-base font-bold text-ir-bone">File Warranty Claim</h3>
                <button @click="openClaimModal = false" class="text-ir-bone/70 hover:text-ir-bone"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form action="{{ route('warranties.file_claim', $warranty) }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-ir-bone/70 uppercase mb-1">Claim Issue Description *</label>
                    <textarea name="issue_description" rows="3" required placeholder="Describe the recurring fault or defect..." class="w-full px-4 py-2.5 rounded-md bg-ir-void border border-ir-copper text-sm text-ir-bone"></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="openClaimModal = false" class="px-4 py-2 rounded-md bg-ir-carbon text-ir-bone text-xs">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-md bg-amber-600 hover:bg-amber-500 text-ir-bone text-xs font-bold shadow-lg">
                        Submit Claim
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
