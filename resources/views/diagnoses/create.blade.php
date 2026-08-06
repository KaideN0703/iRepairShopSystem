@extends('layouts.app')

@section('title', 'Device Inspection & Diagnosis - Ticket #' . $jobOrder->ticket_number)

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="diagnosisForm()">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-ir-bone">Device Inspection & Fault Diagnosis</h2>
            <p class="text-sm text-ir-bone/70 mt-1">Ticket #{{ $jobOrder->ticket_number }} - {{ $jobOrder->device?->brand }} {{ $jobOrder->device?->model }}</p>
        </div>
        
        <div class="flex gap-2">
            <button type="button" @click="fetchAiSuggestions()" class="px-4 py-2 rounded-md bg-ir-carbon border border-ir-copper hover:border-ir-gold text-ir-gold text-xs font-bold flex items-center gap-1.5 transition-colors">
                <i class="fa-solid fa-wand-magic-sparkles text-ir-gold"></i> AI Fault Analysis
            </button>
            <a href="{{ route('job_orders.show', $jobOrder) }}" class="px-4 py-2 rounded-md bg-ir-carbon border border-ir-copper text-ir-bone text-xs font-medium">Back to Ticket</a>
        </div>
    </div>

    <!-- Reported Issue Box -->
    <div class="bg-ir-carbon border border-ir-copper rounded-md p-5">
        <span class="text-xs font-bold text-ir-bone/70 uppercase tracking-wider block">Customer Reported Problem:</span>
        <p class="text-sm text-ir-bone font-semibold mt-1">{{ $jobOrder->reported_issue }}</p>
    </div>

    <!-- AI Suggestion Alert -->
    <div x-show="aiData" class="bg-ir-carbon border border-ir-gold/40 rounded-md p-5 space-y-3" x-cloak>
        <div class="flex items-center justify-between">
            <h4 class="text-sm font-bold text-ir-bone flex items-center gap-2">
                <i class="fa-solid fa-brain text-ir-gold"></i> AI Generated Diagnostic Suggestion
            </h4>
            <span class="text-xs text-ir-amber-deep font-mono" x-text="'Confidence: ' + Math.round((aiData?.confidence || 0.9) * 100) + '%'"></span>
        </div>

        <p class="text-xs font-bold text-ir-gold" x-text="aiData?.diagnosis"></p>
        <ul class="list-disc list-inside text-xs text-ir-bone space-y-1">
            <template x-for="act in (aiData?.recommended_actions || [])" :key="act">
                <li x-text="act"></li>
            </template>
        </ul>

        <button type="button" @click="applyAiSuggestions()" class="px-3 py-1.5 rounded-lg bg-ir-gold hover:bg-ir-amber-deep text-ir-bone text-xs font-bold">
            ⚡ Apply AI Recommendations to Form
        </button>
    </div>

    <form action="{{ route('diagnoses.store', $jobOrder) }}" method="POST" class="bg-ir-carbon border border-ir-copper rounded-md p-6 space-y-6">
        @csrf

        <!-- Inspection Checklist -->
        <div>
            <h4 class="text-xs font-bold text-ir-bone/70 uppercase tracking-wider mb-4 flex items-center gap-2">
                <i class="fa-solid fa-list-check text-ir-gold"></i> Component Inspection Checklist
            </h4>

            @php
                $checkItems = ['power' => 'Power & Boot', 'display' => 'Display Screen', 'touch' => 'Touch Digitizer', 'cameras' => 'Front/Rear Cameras', 'wifi' => 'Wi-Fi & Bluetooth', 'speaker' => 'Loudspeaker', 'mic' => 'Microphone', 'ports' => 'Charging Port', 'housing' => 'Body & Glass Frame'];
                $existingCheck = $diagnosis?->checklist ?? [];
            @endphp

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @foreach($checkItems as $key => $label)
                    <div class="p-3 rounded-md bg-ir-void border border-ir-copper flex items-center justify-between">
                        <span class="text-xs font-medium text-ir-bone">{{ $label }}</span>
                        <div class="flex items-center gap-2">
                            <label class="inline-flex items-center gap-1 cursor-pointer">
                                <input type="radio" name="checklist[{{ $key }}]" value="Pass" {{ ($existingCheck[$key] ?? 'Pass') === 'Pass' ? 'checked' : '' }} class="text-emerald-500 bg-ir-carbon border-ir-copper">
                                <span class="text-xs text-emerald-400 font-bold">Pass</span>
                            </label>
                            <label class="inline-flex items-center gap-1 cursor-pointer">
                                <input type="radio" name="checklist[{{ $key }}]" value="Fail" {{ ($existingCheck[$key] ?? '') === 'Fail' ? 'checked' : '' }} class="text-red-500 bg-ir-carbon border-ir-copper">
                                <span class="text-xs text-red-400 font-bold">Fail</span>
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Identified Issues & Recommended Repairs -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="identified_issues" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Identified Hardware/Software Faults *</label>
                <textarea id="identified_issues" name="identified_issues" rows="4" x-model="identifiedIssues" required class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm focus:border-ir-gold focus:outline-none"></textarea>
            </div>

            <div>
                <label for="recommended_repairs" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Recommended Repairs & Action Steps *</label>
                <textarea id="recommended_repairs" name="recommended_repairs" rows="4" x-model="recommendedRepairs" required class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm focus:border-ir-gold focus:outline-none"></textarea>
            </div>
        </div>

        <!-- Cost & Remarks -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="estimated_cost" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Estimated Repair Cost (₱) *</label>
                <input type="number" step="0.01" id="estimated_cost" name="estimated_cost" x-model="estimatedCost" required class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm focus:border-ir-gold focus:outline-none font-bold text-ir-gold">
            </div>

            <div>
                <label for="technician_remarks" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Technician Remarks</label>
                <input type="text" id="technician_remarks" name="technician_remarks" value="{{ old('technician_remarks', $diagnosis?->technician_remarks) }}" placeholder="Optional technician bench observations..." class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm focus:border-ir-gold focus:outline-none">
            </div>
        </div>

        <div class="pt-4 border-t border-ir-copper flex justify-end gap-3">
            <a href="{{ route('job_orders.show', $jobOrder) }}" class="px-5 py-3 rounded-md bg-ir-carbon text-ir-bone text-sm font-medium">Cancel</a>
            <button type="submit" class="px-6 py-3 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone font-semibold text-sm transition-colors">
                Save Inspection & Diagnosis
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function diagnosisForm() {
        return {
            aiData: null,
            identifiedIssues: @json($diagnosis?->identified_issues ?? $jobOrder->reported_issue),
            recommendedRepairs: @json($diagnosis?->recommended_repairs ?? ''),
            estimatedCost: @json($diagnosis?->estimated_cost ?? 120.00),
            async fetchAiSuggestions() {
                try {
                    const res = await fetch("{{ route('diagnoses.ai_suggestions', $jobOrder) }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ reported_issue: this.identifiedIssues })
                    });
                    const data = await res.json();
                    this.aiData = data;
                } catch (e) {
                    alert('Error reaching AI Diagnosis engine.');
                }
            },
            applyAiSuggestions() {
                if (!this.aiData) return;
                this.identifiedIssues = this.aiData.diagnosis || this.identifiedIssues;
                this.recommendedRepairs = (this.aiData.recommended_actions || []).join("\n");
                this.estimatedCost = this.aiData.estimated_cost || this.estimatedCost;
            }
        }
    }
</script>
@endpush
@endsection
