{{-- Progress Tab Partial --}}
{{-- Variables inherited: $jobOrder, $stages --}}
{{-- Note: Uses Alpine state from parent x-data="jobOrderWorkspace()" --}}

<div class="space-y-6">

    {{-- Post Progress Update Form --}}
    <div class="bg-ir-void border border-ir-copper rounded-md p-5 space-y-5">
        <h4 class="text-sm font-bold text-ir-bone uppercase tracking-wider flex items-center gap-2 border-b border-ir-copper pb-3">
            <i class="fa-solid fa-circle-plus text-ir-gold"></i> Post Live Progress Update
            <span class="text-xs text-ir-bone/50 font-normal normal-case ml-auto">Broadcasts to customer tracker in real-time</span>
        </h4>

        <form action="{{ route('job_orders.progress_updates.store', $jobOrder) }}" method="POST"
              enctype="multipart/form-data" class="space-y-5">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                {{-- Pipeline Stage --}}
                <div>
                    <label for="progress_pipeline_stage" class="ir-label">Pipeline Stage *</label>
                    <select id="progress_pipeline_stage" name="pipeline_stage"
                            x-model="selectedStage" @change="onStageChange()" required class="ir-select">
                        @foreach($stages as $st)
                            <option value="{{ $st }}">
                                {{ $st }} ({{ \App\Models\JobOrder::STAGE_PERCENTAGE_RANGES[$st][0] }}% – {{ \App\Models\JobOrder::STAGE_PERCENTAGE_RANGES[$st][1] }}%)
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Percentage Slider --}}
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <label for="progress_pct" class="ir-label !mb-0">Completion Percentage *</label>
                        <span class="text-sm font-bold text-ir-gold font-mono" x-text="percentage + '%'"></span>
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="range" id="progress_pct" name="percentage" min="0" max="100"
                               x-model="percentage" class="flex-1 accent-amber-500 h-2 bg-ir-carbon rounded-lg cursor-pointer">
                        <input type="number" name="percentage" min="0" max="100" x-model="percentage"
                               class="w-20 px-3 py-2 rounded-md bg-ir-carbon border border-ir-copper text-sm font-bold text-center text-ir-bone focus:outline-none focus:border-ir-gold">
                    </div>
                </div>
            </div>

            {{-- Rework Detection Alert --}}
            <div x-show="isRework" class="p-4 rounded-md bg-amber-500/10 border border-amber-500/30 space-y-2" x-cloak>
                <div class="flex items-center gap-2 text-xs font-bold text-amber-300">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>Percentage Drop Detected ({{ $jobOrder->current_percentage }}% → <span x-text="percentage"></span>%) — Rework Reason Required</span>
                </div>
                <input type="text" name="rework_reason" :required="isRework"
                       placeholder="Explain reason for rework (e.g., replacement screen failed touch test)..."
                       class="ir-input text-xs">
            </div>

            {{-- Description & Photo Upload --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label for="progress_desc" class="ir-label">Progress Update Description *</label>
                    <textarea id="progress_desc" name="description" rows="3" required
                              placeholder="Describe progress made (e.g., motherboard cleaned, testing power rail)..."
                              class="ir-input"></textarea>
                </div>
                <div>
                    <label class="ir-label">Upload Milestone Photo(s) * (1–5 photos)</label>
                    <input type="file" name="photos[]" multiple accept="image/*" capture="environment" required
                           class="w-full text-xs text-ir-bone/70 file:mr-3 file:py-2.5 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-ir-gold file:text-ir-bone hover:file:bg-ir-amber-deep cursor-pointer">
                    <span class="block text-[11px] text-ir-copper mt-1">Photos are auto-compressed to max 1600px with 300×300 thumbnails.</span>
                </div>
            </div>

            {{-- Visibility Toggle: Two explicit buttons (Task 4) --}}
            <div class="bg-ir-carbon border border-ir-copper rounded-md p-4 space-y-3">
                <p class="text-xs font-semibold text-ir-bone">
                    <i class="fa-solid fa-eye-low-vision text-ir-copper mr-1"></i>
                    Note Visibility — Choose carefully:
                </p>
                <div class="flex flex-col sm:flex-row gap-3" x-data="{ noteVisibility: 'internal' }">
                    <input type="hidden" name="is_customer_visible" :value="noteVisibility === 'customer' ? '1' : '0'">

                    <button type="button"
                            @click="noteVisibility = 'internal'"
                            :class="noteVisibility === 'internal'
                                ? 'bg-ir-copper/30 border-ir-copper text-ir-bone'
                                : 'border-ir-copper/40 text-ir-bone/50 hover:text-ir-bone hover:border-ir-copper'"
                            class="flex-1 flex items-center gap-2 px-4 py-2.5 rounded-md border text-xs font-semibold transition-colors">
                        <i class="fa-solid fa-lock text-ir-copper"></i>
                        <span>Save as Internal Note Only</span>
                        <span x-show="noteVisibility === 'internal'" class="ml-auto text-ir-copper text-[10px] font-bold">SELECTED</span>
                    </button>

                    {{-- Confirm strip before posting customer-visible note (Task 4) --}}
                    <div class="flex-1" x-data="{ confirmingPublic: false }">
                        <template x-if="!confirmingPublic">
                            <button type="button"
                                    @click="confirmingPublic = true; noteVisibility = 'customer'"
                                    :class="noteVisibility === 'customer'
                                        ? 'bg-ir-gold/20 border-ir-gold text-ir-gold'
                                        : 'border-ir-copper/40 text-ir-bone/50 hover:text-ir-bone hover:border-ir-gold'"
                                    class="w-full flex items-center gap-2 px-4 py-2.5 rounded-md border text-xs font-semibold transition-colors">
                                <i class="fa-solid fa-eye text-ir-gold"></i>
                                <span>Post Update to Customer</span>
                            </button>
                        </template>
                        <template x-if="confirmingPublic">
                            <div class="flex items-center gap-2 px-3 py-2.5 rounded-md border border-ir-gold bg-ir-gold/10 text-xs">
                                <i class="fa-solid fa-triangle-exclamation text-ir-gold"></i>
                                <span class="text-ir-bone flex-1">This will be <strong class="text-ir-gold">visible to the customer</strong>. Confirm?</span>
                                <button type="button" @click="noteVisibility = 'internal'; confirmingPublic = false" class="text-ir-copper hover:text-ir-bone px-2">Cancel</button>
                                <span class="text-ir-gold font-bold">✓ Confirmed</span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Optional Approval Request --}}
            <div>
                <button type="button" @click="showApprovalSection = !showApprovalSection"
                        class="text-xs text-ir-gold hover:underline flex items-center gap-1 font-semibold">
                    <i class="fa-solid fa-file-circle-plus"></i>
                    <span x-text="showApprovalSection ? '– Hide Approval Request' : '+ Attach Customer Approval Request (Extra Cost/Time)'"></span>
                </button>
            </div>

            <div x-show="showApprovalSection" class="p-5 rounded-md bg-amber-950/20 border border-amber-500/30 space-y-4" x-cloak>
                <h4 class="text-xs font-bold text-amber-300 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-file-invoice-dollar text-amber-400"></i> Customer Approval Request
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="ir-label">Request Title *</label>
                        <input type="text" name="approval_title" placeholder="e.g. Additional IC Chip Replacement" class="ir-input text-xs">
                    </div>
                    <div>
                        <label class="ir-label">Additional Cost (₱)</label>
                        <input type="number" step="0.01" name="additional_cost" placeholder="0.00" class="ir-input text-xs font-bold text-right">
                    </div>
                    <div>
                        <label class="ir-label">Extra Time Impact (Days)</label>
                        <input type="number" name="additional_time_days" placeholder="1" class="ir-input text-xs">
                    </div>
                </div>
                <div>
                    <label class="ir-label">Reason & Customer Note *</label>
                    <textarea name="approval_description" rows="2"
                              placeholder="Explain what was discovered and why additional repair is needed..."
                              class="ir-input text-xs"></textarea>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn-primary">
                    <i class="fa-solid fa-paper-plane"></i> Post Update & Broadcast Live
                </button>
            </div>
        </form>
    </div>

    {{-- Milestone Feed --}}
    <div class="bg-ir-void border border-ir-copper rounded-md p-5 space-y-4">
        <h4 class="text-xs font-bold text-ir-bone/70 uppercase tracking-wider flex items-center justify-between border-b border-ir-copper pb-3">
            <span><i class="fa-solid fa-clock-rotate-left text-ir-gold mr-1"></i> Live Milestone Feed</span>
            <span class="text-[10px] text-ir-bone/70">{{ $jobOrder->progressUpdates->count() }} updates</span>
        </h4>

        <div class="space-y-4 max-h-[500px] overflow-y-auto pr-1 text-xs">
            @forelse($jobOrder->progressUpdates as $up)
                <div class="p-3.5 rounded-md bg-ir-carbon border border-ir-copper space-y-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-ir-gold font-mono">{{ $up->percentage }}%</span>
                            <x-status-badge :stage="$up->pipeline_stage" />
                            {{-- Eye icon for customer-visible notes (Task 4) --}}
                            @if($up->is_customer_visible)
                                <span class="flex items-center gap-1 px-1.5 py-0.5 rounded bg-ir-gold/10 border border-ir-gold/20 text-[10px] text-ir-gold" title="Visible to customer">
                                    <i class="fa-solid fa-eye"></i>
                                </span>
                            @else
                                <span class="flex items-center gap-1 px-1.5 py-0.5 rounded bg-ir-copper/10 border border-ir-copper/30 text-[10px] text-ir-copper" title="Internal only">
                                    <i class="fa-solid fa-eye-slash"></i>
                                </span>
                            @endif
                        </div>
                        <span class="text-[10px] text-ir-copper">{{ $up->created_at->diffForHumans() }}</span>
                    </div>

                    @if($up->is_rework)
                        <div class="px-2 py-1 rounded bg-amber-500/10 border border-amber-500/20 text-amber-300 text-[10px] font-semibold">
                            ⚠️ Rework: {{ $up->rework_reason }}
                        </div>
                    @endif

                    <p class="text-ir-bone">{{ $up->description }}</p>

                    @if($up->approvalRequest)
                        <div class="p-2 rounded bg-amber-950/40 border border-amber-500/30 text-[11px] space-y-1">
                            <div class="flex items-center justify-between">
                                <strong class="text-amber-300">Approval Request: {{ $up->approvalRequest->title }}</strong>
                                <span class="px-2 py-0.5 rounded font-bold uppercase text-[9px]
                                    @if($up->approvalRequest->status === 'approved') bg-emerald-500/20 text-emerald-300
                                    @elseif($up->approvalRequest->status === 'declined') bg-red-500/20 text-red-300
                                    @else bg-amber-500/20 text-amber-300 @endif">
                                    {{ $up->approvalRequest->status }}
                                </span>
                            </div>
                            <p class="text-ir-bone"><x-currency :amount="$up->approvalRequest->additional_cost" /> additional | +{{ $up->approvalRequest->additional_time_days }} day(s)</p>
                        </div>
                    @endif

                    {{-- Milestone Photos --}}
                    <div class="grid grid-cols-3 gap-2 pt-1">
                        @foreach($up->photos as $ph)
                            <div class="relative cursor-pointer overflow-hidden rounded-lg border border-ir-copper group"
                                 @click="openPhotoModal('{{ $ph->file_path }}', 'progress_photo', {{ $ph->id }})">
                                <img src="{{ $ph->thumbnail_path }}" alt="Milestone" class="h-16 w-full object-cover group-hover:scale-105 transition-transform">
                                <div class="absolute bottom-1 right-1 bg-ir-carbon/90 text-ir-gold text-[9px] font-bold px-1.5 py-0.5 rounded border border-ir-copper flex items-center gap-1 shadow">
                                    <i class="fa-solid fa-comments"></i> {{ $ph->comments->count() }}
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex items-center justify-between text-[10px] text-ir-copper pt-1">
                        <span>By: {{ $up->user?->name ?? 'Staff' }}</span>
                    </div>
                </div>
            @empty
                <div class="text-center text-ir-copper py-8">No milestone updates posted yet.</div>
            @endforelse
        </div>
    </div>

</div>
