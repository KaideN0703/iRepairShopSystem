{{-- Warranty Tab Partial --}}
{{-- Variables inherited: $jobOrder --}}

<div class="space-y-5" x-data="{ openClaimModal: false }">

    @if($jobOrder->warranty)
        @php $warranty = $jobOrder->warranty; @endphp

        {{-- Warranty Status Card --}}
        <div class="bg-ir-void border border-ir-copper rounded-md p-5 space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-ir-gold/10 border border-ir-gold/30 flex items-center justify-center text-ir-gold">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-ir-bone">{{ $warranty->warranty_period_days }}-Day Warranty</h4>
                        <span class="text-xs text-ir-bone/70">{{ $warranty->start_date?->format('M d, Y') }} – {{ $warranty->end_date?->format('M d, Y') }}</span>
                    </div>
                </div>
                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase
                    @if($warranty->status === 'active') bg-emerald-500/20 text-emerald-300 border border-emerald-500/30
                    @elseif($warranty->status === 'claimed') bg-amber-500/20 text-amber-300 border border-amber-500/30
                    @else bg-ir-copper/20 text-ir-copper border border-ir-copper/30 @endif">
                    {{ ucfirst($warranty->status) }}
                </span>
            </div>

            @if($warranty->coverage_details)
            <div class="bg-ir-carbon rounded-md p-3 text-xs text-ir-bone border border-ir-copper/40">
                <span class="block text-[10px] font-bold text-ir-bone/60 uppercase mb-1">Coverage Details</span>
                {{ $warranty->coverage_details }}
            </div>
            @endif

            @php
                $daysLeft = now()->diffInDays($warranty->end_date, false);
            @endphp
            @if($daysLeft > 0 && $warranty->status === 'active')
            <div class="flex items-center gap-2 text-xs text-ir-signal-green">
                <i class="fa-solid fa-clock"></i>
                <span><strong>{{ $daysLeft }}</strong> days remaining on warranty coverage</span>
            </div>
            @elseif($daysLeft <= 0)
            <div class="flex items-center gap-2 text-xs text-ir-alert">
                <i class="fa-solid fa-circle-xmark"></i>
                <span>Warranty period has expired.</span>
            </div>
            @endif
        </div>

        {{-- File Claim Button --}}
        @if($warranty->status === 'active')
        <div class="flex justify-end">
            <button @click="openClaimModal = true" class="btn-secondary">
                <i class="fa-solid fa-file-circle-exclamation"></i> File Warranty Claim
            </button>
        </div>
        @endif

        {{-- Claim History --}}
        <div class="bg-ir-void border border-ir-copper rounded-md p-5 space-y-3">
            <h4 class="text-xs font-bold text-ir-bone/70 uppercase tracking-wider border-b border-ir-copper pb-3">
                <i class="fa-solid fa-list-check text-ir-gold mr-1"></i>
                Warranty Claims ({{ $warranty->claims->count() }})
            </h4>
            <div class="space-y-3">
                @forelse($warranty->claims as $claim)
                <div class="p-4 rounded-md bg-ir-carbon border border-ir-copper space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-amber-400 font-mono text-sm">#{{ $claim->claim_number }}</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-ir-void text-ir-bone border border-ir-copper">
                            {{ $claim->resolution_status }}
                        </span>
                    </div>
                    <p class="text-xs text-ir-bone">{{ $claim->issue_description }}</p>
                    @if($claim->resolution_notes)
                    <p class="text-xs text-ir-bone/70 italic">Resolution: {{ $claim->resolution_notes }}</p>
                    @endif
                    <p class="text-[10px] text-ir-copper">Filed: {{ $claim->claim_date?->format('M d, Y') }}</p>
                </div>
                @empty
                <div class="text-center py-6">
                    <p class="text-xs font-medium text-ir-bone/70">No warranty claims have been filed for this device.</p>
                    <p class="text-[11px] text-ir-bone/50 mt-0.5">If the customer returns with recurring issues during the coverage period, click "File Warranty Claim" above.</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- File Claim Modal --}}
        <div x-show="openClaimModal" class="fixed inset-0 z-50 bg-ir-void/80 backdrop-blur-sm flex items-center justify-center p-4" x-cloak>
            <div class="bg-ir-carbon border border-ir-copper rounded-md p-6 max-w-md w-full space-y-4">
                <div class="flex items-center justify-between border-b border-ir-copper pb-3">
                    <h3 class="text-base font-bold text-ir-bone">File Warranty Claim</h3>
                    <button @click="openClaimModal = false" class="text-ir-bone/70 hover:text-ir-bone"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <form action="{{ route('warranties.file_claim', $warranty) }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="ir-label">Claim Issue Description *</label>
                        <textarea name="issue_description" rows="3" required placeholder="Describe the recurring issue reported by customer..." class="ir-input text-xs"></textarea>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="openClaimModal = false" class="btn-secondary btn-sm">Cancel</button>
                        <button type="submit" class="btn-primary">Submit Claim</button>
                    </div>
                </form>
            </div>
        </div>

    @else
        {{-- No Warranty Registered Yet --}}
        <div class="bg-ir-void border border-ir-copper/60 rounded-md p-10 text-center">
            <div class="max-w-md mx-auto space-y-3">
                <div class="w-12 h-12 rounded-full bg-ir-carbon border border-ir-copper text-ir-gold flex items-center justify-center mx-auto text-xl shadow-inner">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h4 class="text-sm font-bold text-ir-bone">No warranty registered for this job order yet</h4>
                <p class="text-xs text-ir-bone/60 leading-relaxed">
                    A standard 90-day post-repair warranty coverage is automatically registered upon device release to the customer.
                </p>
                @if(in_array($jobOrder->status, ['Released', 'Completed']))
                    <div class="p-3 rounded bg-red-500/10 border border-red-500/30 text-xs text-red-300">
                        <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                        Device is marked <strong>{{ $jobOrder->status }}</strong> but no warranty record was created. Contact administrator to generate a manual warranty certificate.
                    </div>
                @endif
            </div>
        </div>
    @endif

</div>
