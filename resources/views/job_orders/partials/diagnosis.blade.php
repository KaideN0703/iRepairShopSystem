{{-- Diagnosis Tab Partial --}}
{{-- Variables inherited: $jobOrder --}}

<div class="space-y-5">

    {{-- Action Button --}}
    <div class="flex items-center justify-between">
        <p class="text-xs text-ir-bone/70">Review the initial physical inspection checklist and diagnostic findings for this ticket.</p>
        <a href="{{ route('diagnoses.create', $jobOrder) }}" class="btn-primary btn-sm">
            <i class="fa-solid fa-clipboard-check"></i>
            {{ $jobOrder->diagnosis ? 'Edit Technical Inspection' : 'Record Device Inspection' }}
        </a>
    </div>

    @if($jobOrder->diagnosis)

        {{-- Inspection Checklist Grid --}}
        @if(!empty($jobOrder->diagnosis->checklist))
        <div class="bg-ir-void border border-ir-copper rounded-md p-5">
            <h4 class="text-xs font-bold text-ir-bone/70 uppercase tracking-wider mb-3">
                <i class="fa-solid fa-list-check text-ir-gold mr-1"></i> Physical Inspection Checklist
            </h4>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
                @foreach($jobOrder->diagnosis->checklist as $item => $status)
                    <div class="p-2.5 rounded bg-ir-carbon border border-ir-copper flex items-center justify-between">
                        <span class="capitalize text-ir-bone/70">{{ $item }}</span>
                        <span class="font-bold {{ $status === 'Pass' ? 'text-ir-signal-green' : 'text-ir-alert' }}">
                            {{ $status }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Diagnosis Details --}}
        <div class="bg-ir-void border border-ir-copper rounded-md p-5 space-y-4">
            <h4 class="text-xs font-bold text-ir-bone/70 uppercase tracking-wider">
                <i class="fa-solid fa-stethoscope text-ir-gold mr-1"></i> Technician Diagnosis Report
            </h4>

            @if($jobOrder->diagnosis->identified_issues)
            <div>
                <span class="block text-xs font-bold text-ir-amber-deep mb-1">Identified Faults:</span>
                <p class="text-sm text-ir-bone bg-ir-carbon p-3 rounded border border-ir-copper">{{ $jobOrder->diagnosis->identified_issues }}</p>
            </div>
            @endif

            @if($jobOrder->diagnosis->recommended_repairs)
            <div>
                <span class="block text-xs font-bold text-ir-amber-deep mb-1">Recommended Repairs:</span>
                <p class="text-sm text-ir-bone bg-ir-carbon p-3 rounded border border-ir-copper">{{ $jobOrder->diagnosis->recommended_repairs }}</p>
            </div>
            @endif

            @if($jobOrder->diagnosis->technician_remarks)
            <div>
                <span class="block text-xs font-bold text-ir-amber-deep mb-1">Technician Notes:</span>
                <p class="text-xs text-ir-bone/80 bg-ir-carbon p-3 rounded border border-ir-copper">{{ $jobOrder->diagnosis->technician_remarks }}</p>
            </div>
            @endif

            @if($jobOrder->diagnosis->estimated_cost > 0)
            <div class="flex items-center gap-3 text-xs pt-1 border-t border-ir-copper/40">
                <span class="text-ir-bone/70">Estimated Repair Cost:</span>
                <span class="font-bold text-ir-gold font-mono text-sm"><x-currency :amount="$jobOrder->diagnosis->estimated_cost" /></span>
            </div>
            @endif
        </div>

        {{-- AI Diagnostic Suggestions --}}
        @if(!empty($jobOrder->diagnosis->ai_suggestions))
        <div class="bg-ir-void border border-ir-gold/30 rounded-md p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-ir-amber-deep flex items-center gap-1.5">
                    <i class="fa-solid fa-wand-magic-sparkles text-amber-400"></i> AI Diagnostic Recommendation
                </span>
                <span class="px-2 py-0.5 rounded bg-ir-amber-deep/20 text-ir-amber-deep text-[10px] font-mono">
                    Confidence: {{ ($jobOrder->diagnosis->ai_suggestions['confidence'] ?? 0.9) * 100 }}%
                </span>
            </div>
            @if(!empty($jobOrder->diagnosis->ai_suggestions['diagnosis']))
            <p class="text-sm text-ir-bone font-semibold">{{ $jobOrder->diagnosis->ai_suggestions['diagnosis'] }}</p>
            @endif
            @if(!empty($jobOrder->diagnosis->ai_suggestions['recommended_actions']))
            <ul class="list-disc list-inside space-y-1 text-xs text-ir-bone">
                @foreach($jobOrder->diagnosis->ai_suggestions['recommended_actions'] as $act)
                    <li>{{ $act }}</li>
                @endforeach
            </ul>
            @endif
        </div>
        @endif

    @else
        <div class="bg-ir-void border border-ir-copper/60 rounded-md p-10 text-center">
            <div class="max-w-md mx-auto space-y-3">
                <div class="w-12 h-12 rounded-full bg-ir-carbon border border-ir-copper text-ir-gold flex items-center justify-center mx-auto text-xl shadow-inner">
                    <i class="fa-solid fa-stethoscope"></i>
                </div>
                <h4 class="text-sm font-bold text-ir-bone">No technical diagnosis logged yet</h4>
                <p class="text-xs text-ir-bone/60 leading-relaxed">
                    Complete the physical inspection checklist and log your diagnostic findings to guide the repair process and estimate costs.
                </p>
                <div class="pt-2">
                    <a href="{{ route('diagnoses.create', $jobOrder) }}" class="btn-primary btn-sm">
                        <i class="fa-solid fa-clipboard-check"></i> Record Device Inspection
                    </a>
                </div>
            </div>
        </div>
    @endif

</div>


</div>
