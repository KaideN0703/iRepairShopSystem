@extends('layouts.app')

@section('title', 'Ticket #' . $jobOrder->ticket_number)

@section('content')
<div class="space-y-6" x-data="jobOrderWorkspace()">

    <!-- Header Bar -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-ir-carbon border border-ir-copper p-6 rounded-md">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="text-2xl font-extrabold text-ir-bone">Ticket #{{ $jobOrder->ticket_number }}</h2>
                <span class="px-3 py-1 rounded-full text-xs font-bold border 
                    @if($jobOrder->status === 'Released') bg-teal-500/10 text-teal-400 border-teal-500/30
                    @elseif($jobOrder->status === 'Ready for Pickup') bg-emerald-500/10 text-emerald-400 border-emerald-500/30
                    @elseif($jobOrder->status === 'Under Repair') bg-blue-500/10 text-blue-400 border-blue-500/30
                    @else bg-ir-carbon text-ir-bone border-ir-copper @endif">
                    {{ $jobOrder->status }} ({{ $jobOrder->current_percentage }}%)
                </span>
                
                @if($jobOrder->pendingApprovalRequest)
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-500/40 flex items-center gap-1.5">
                        <i class="fa-solid fa-clock-rotate-left"></i> Customer Approval Pending
                    </span>
                @endif

                <span class="text-xs text-amber-400 font-semibold px-2 py-0.5 rounded bg-amber-500/10 border border-amber-500/20">
                    Priority: {{ $jobOrder->priority }}
                </span>
            </div>
            <p class="text-xs text-ir-bone/70 mt-1">
                Created on {{ $jobOrder->created_at->format('M d, Y h:i A') }} | Est. Completion: {{ $jobOrder->estimated_completion_date ? $jobOrder->estimated_completion_date->format('M d, Y') : 'N/A' }}
            </p>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
            <a href="{{ route('track.show', $jobOrder->tracking_token ?? $jobOrder->ticket_number) }}" target="_blank" class="px-4 py-2 rounded-md bg-ir-carbon hover:bg-ir-carbon text-ir-gold border border-ir-copper text-xs font-semibold">
                <i class="fa-solid fa-eye mr-1"></i> Public Tracking Link
            </a>

            <a href="{{ route('job_orders.receipt', $jobOrder) }}" target="_blank" class="px-4 py-2 rounded-md bg-ir-carbon hover:bg-ir-carbon text-ir-bone text-xs font-medium transition-colors">
                <i class="fa-solid fa-print mr-1"></i> Print Receipt
            </a>

            @if(!$jobOrder->invoice)
                <form action="{{ route('invoices.generate', $jobOrder) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-md bg-emerald-600 hover:bg-emerald-500 text-ir-bone text-xs font-semibold transition-colors">
                        <i class="fa-solid fa-file-invoice-dollar mr-1"></i> Generate Invoice
                    </button>
                </form>
            @else
                <a href="{{ route('invoices.show', $jobOrder->invoice) }}" class="px-4 py-2 rounded-md bg-emerald-600/20 text-emerald-300 border border-emerald-500/30 text-xs font-semibold">
                    <i class="fa-solid fa-file-invoice mr-1"></i> View Invoice ({{ $jobOrder->invoice->invoice_number }})
                </a>
            @endif

            <button @click="openSigModal = true" class="px-4 py-2 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone text-xs font-semibold transition-colors">
                <i class="fa-solid fa-signature mr-1"></i> Capture Signature & Release
            </button>
        </div>
    </div>

    <!-- Status Pipeline Stepper with Live Percentage Progress -->
    <div class="bg-ir-carbon border border-ir-copper rounded-md p-6 space-y-4">
        <div class="flex items-center justify-between">
            <h4 class="text-xs font-bold text-ir-bone/70 uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-route text-ir-gold"></i> Repair Progress: {{ $jobOrder->current_percentage }}%
            </h4>
            <span class="text-xs text-ir-gold font-semibold">Current Stage: {{ $jobOrder->status }}</span>
        </div>

        <!-- Progress Bar -->
        <div class="w-full bg-ir-void h-3 rounded-full overflow-hidden p-0.5 border border-ir-copper">
            <div class="h-full bg-ir-gold rounded-full transition-all duration-500" style="width: {{ $jobOrder->current_percentage }}%"></div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-2">
            @foreach($stages as $stage)
                @php
                    $isCurrent = $jobOrder->status === $stage;
                @endphp
                <form action="{{ route('job_orders.update_status', $jobOrder) }}" method="POST" class="w-full">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="{{ $stage }}">
                    <button type="submit" class="w-full py-2.5 px-2 rounded-md text-xs font-bold transition-colors border text-center
                        {{ $isCurrent ? 'bg-ir-gold text-ir-bone border-ir-gold' : 'bg-ir-void text-ir-bone/70 border-ir-copper hover:bg-ir-carbon hover:text-ir-bone' }}">
                        <span class="block truncate">{{ $stage }}</span>
                    </button>
                </form>
            @endforeach
        </div>
    </div>

    <!-- POST LIVE PROGRESS UPDATE PANEL -->
    <div class="bg-ir-carbon border border-ir-copper rounded-md p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-ir-copper pb-3">
            <h3 class="text-sm font-bold text-ir-bone uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-circle-plus text-ir-gold"></i> Post Live Repair Progress Update (Photos & Milestone)
            </h3>
            <span class="text-xs text-ir-bone/70">Updates live customer tracking portal</span>
        </div>

        <form action="{{ route('job_orders.progress_updates.store', $jobOrder) }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Pipeline Stage -->
                <div>
                    <label for="pipeline_stage" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Pipeline Stage *</label>
                    <select id="pipeline_stage" name="pipeline_stage" x-model="selectedStage" @change="onStageChange()" required class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm focus:border-ir-gold focus:outline-none">
                        @foreach($stages as $st)
                            <option value="{{ $st }}">{{ $st }} (Range: {{ \App\Models\JobOrder::STAGE_PERCENTAGE_RANGES[$st][0] }}% - {{ \App\Models\JobOrder::STAGE_PERCENTAGE_RANGES[$st][1] }}%)</option>
                        @endforeach
                    </select>
                </div>

                <!-- Percentage Slider -->
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label for="percentage" class="text-xs font-semibold text-ir-bone uppercase tracking-wider">Completion Percentage *</label>
                        <span class="text-sm font-bold text-ir-gold font-mono" x-text="percentage + '%'"></span>
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="range" id="percentage" name="percentage" min="0" max="100" x-model="percentage" class="flex-1 accent-indigo-500 h-2 bg-ir-void rounded-lg cursor-pointer">
                        <input type="number" name="percentage" min="0" max="100" x-model="percentage" class="w-20 px-3 py-2 rounded-md bg-ir-void border border-ir-copper text-sm font-bold text-center text-ir-bone">
                    </div>
                </div>
            </div>

            <!-- Rework Detection Alert & Reason Field -->
            <div x-show="isRework" class="p-4 rounded-md bg-amber-500/10 border border-amber-500/30 space-y-2" x-cloak>
                <div class="flex items-center gap-2 text-xs font-bold text-amber-300">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>Percentage Drop Detected ({{ $jobOrder->current_percentage }}% → <span x-text="percentage"></span>%) - Rework Reason Required</span>
                </div>
                <input type="text" name="rework_reason" x-bind:required="isRework" placeholder="Explain reason for rework (e.g., replacement screen failed touch test)..." class="w-full px-4 py-2.5 rounded-md bg-ir-void border border-ir-copper text-xs text-ir-bone focus:border-amber-500 focus:outline-none">
            </div>

            <!-- Description & Photo Upload -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label for="description" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Progress Update Description *</label>
                    <textarea id="description" name="description" rows="3" required placeholder="Describe progress made (e.g., motherboard ultrasonic cleaned, testing power rail)..." class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm focus:border-ir-gold focus:outline-none"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Upload Milestone Photo(s) * (1-4 Photos, Camera Enabled)</label>
                    <input type="file" name="photos[]" multiple accept="image/*" capture="environment" required class="w-full text-xs text-ir-bone/70 file:mr-3 file:py-2.5 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-ir-gold file:text-ir-bone hover:file:bg-ir-amber-deep cursor-pointer">
                    <span class="block text-[11px] text-ir-copper mt-1">Photos are auto-compressed to max 1600px with 300x300 thumbnails.</span>
                </div>
            </div>

            <!-- Visibility Toggle & Approval Toggle -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 p-4 rounded-md bg-ir-void border border-ir-copper">
                <label class="flex items-center gap-2 cursor-pointer text-xs font-medium text-ir-bone">
                    <input type="checkbox" name="is_customer_visible" value="1" checked class="rounded bg-ir-carbon border-ir-copper text-indigo-600">
                    <span>Visible to Customer on Live Tracker</span>
                </label>

                <button type="button" @click="showApprovalSection = !showApprovalSection" class="text-xs text-amber-400 hover:underline flex items-center gap-1 font-semibold">
                    <i class="fa-solid fa-file-circle-plus"></i>
                    <span x-text="showApprovalSection ? '- Hide Approval Request' : '+ Attach Customer Approval Request (Extra Cost/Time)'"></span>
                </button>
            </div>

            <!-- Customer Approval Request Sub-Form -->
            <div x-show="showApprovalSection" class="p-5 rounded-md bg-amber-950/20 border border-amber-500/30 space-y-4" x-cloak>
                <h4 class="text-xs font-bold text-amber-300 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-file-invoice-dollar text-amber-400"></i> Customer Approval Request (Requires Customer Consent to Proceed)
                </h4>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-ir-bone mb-1">Request Title *</label>
                        <input type="text" name="approval_title" placeholder="e.g. Additional IC Chip Replacement" class="w-full px-3 py-2 rounded-md bg-ir-void border border-ir-copper text-xs text-ir-bone">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-ir-bone mb-1">Additional Cost (₱)</label>
                        <input type="number" step="0.01" name="additional_cost" placeholder="40.00" class="w-full px-3 py-2 rounded-md bg-ir-void border border-ir-copper text-xs text-ir-bone font-bold">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-ir-bone mb-1">Extra Time Impact (Days)</label>
                        <input type="number" name="additional_time_days" placeholder="1" class="w-full px-3 py-2 rounded-md bg-ir-void border border-ir-copper text-xs text-ir-bone">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-ir-bone mb-1">Reason & Customer Note *</label>
                    <textarea name="approval_description" rows="2" placeholder="Explain what was discovered and why additional repair is needed..." class="w-full px-3 py-2 rounded-md bg-ir-void border border-ir-copper text-xs text-ir-bone"></textarea>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-6 py-3 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone font-semibold text-xs flex items-center gap-2 transition-colors">
                    <i class="fa-solid fa-paper-plane"></i> Post Update & Broadcast Live
                </button>
            </div>
        </form>
    </div>

    <!-- Main Workspace Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left 2 Columns -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Customer & Device Overview Card -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                
                <!-- Customer Card -->
                <div class="bg-ir-carbon border border-ir-copper rounded-md p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold text-ir-bone/70 uppercase tracking-wider"><i class="fa-solid fa-user text-ir-gold mr-1"></i> Customer Profile</span>
                        <a href="{{ route('customers.show', $jobOrder->customer_id) }}" class="text-xs text-ir-gold hover:underline">View History</a>
                    </div>
                    <h3 class="text-lg font-bold text-ir-bone">{{ $jobOrder->customer?->name }}</h3>
                    <div class="text-xs text-ir-bone space-y-1 mt-2">
                        <p><i class="fa-solid fa-id-card text-ir-copper w-4"></i> Code: {{ $jobOrder->customer?->customer_code }}</p>
                        <p><i class="fa-solid fa-phone text-ir-copper w-4"></i> {{ $jobOrder->customer?->phone }}</p>
                        <p><i class="fa-solid fa-envelope text-ir-copper w-4"></i> {{ $jobOrder->customer?->email ?? 'No email' }}</p>
                        <p><i class="fa-solid fa-location-dot text-ir-copper w-4"></i> {{ $jobOrder->customer?->address ?? 'No address' }}</p>
                    </div>
                </div>

                <!-- Device Card -->
                <div class="bg-ir-carbon border border-ir-copper rounded-md p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold text-ir-bone/70 uppercase tracking-wider"><i class="fa-solid fa-mobile-screen text-ir-gold mr-1"></i> Device Specifications</span>
                        <span class="text-xs text-ir-bone/70 font-mono">S/N: {{ $jobOrder->device?->serial_number ?? 'N/A' }}</span>
                    </div>
                    <h3 class="text-lg font-bold text-ir-bone">{{ $jobOrder->device?->brand }} {{ $jobOrder->device?->model }}</h3>
                    <div class="text-xs text-ir-bone space-y-1 mt-2">
                        <p><i class="fa-solid fa-microchip text-ir-copper w-4"></i> Category: {{ $jobOrder->device?->device_type }}</p>
                        <p><i class="fa-solid fa-palette text-ir-copper w-4"></i> Color: {{ $jobOrder->device?->color ?? 'Unspecified' }}</p>
                        <p><i class="fa-solid fa-key text-ir-copper w-4"></i> Passcode/Pattern: <strong class="text-amber-400 font-mono">{{ $jobOrder->device?->passcode_pattern ?? 'None' }}</strong></p>
                    </div>
                </div>

            </div>

            <!-- Reported Issue & Diagnosis Inspection Section -->
            <div class="bg-ir-carbon border border-ir-copper rounded-md p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-ir-copper pb-3">
                    <h4 class="text-sm font-bold text-ir-bone uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-stethoscope text-ir-gold"></i> Reported Issue & Initial Diagnosis
                    </h4>

                    <a href="{{ route('diagnoses.create', $jobOrder) }}" class="px-3 py-1.5 rounded-lg bg-ir-gold hover:bg-ir-amber-deep text-ir-bone text-xs font-semibold">
                        <i class="fa-solid fa-clipboard-check mr-1"></i> {{ $jobOrder->diagnosis ? 'Edit Inspection Form' : 'Fill Inspection Checklist' }}
                    </a>
                </div>

                <div class="p-4 rounded-md bg-ir-void border border-ir-copper">
                    <span class="block text-xs font-semibold text-ir-bone/70 uppercase">Customer Reported Symptoms:</span>
                    <p class="text-sm text-ir-bone mt-1 font-medium">{{ $jobOrder->reported_issue }}</p>
                </div>

                @if($jobOrder->diagnosis)
                    <div class="space-y-3">
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
                            @foreach($jobOrder->diagnosis->checklist ?? [] as $item => $status)
                                <div class="p-2 rounded bg-ir-void border border-ir-copper flex items-center justify-between">
                                    <span class="capitalize text-ir-bone/70">{{ $item }}</span>
                                    <span class="font-bold {{ $status === 'Pass' ? 'text-emerald-400' : 'text-red-400' }}">{{ $status }}</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="p-4 rounded-md bg-indigo-950/40 border border-ir-copper/60 text-xs space-y-2">
                            <div>
                                <span class="font-bold text-ir-amber-deep">Identified Issues:</span>
                                <p class="text-ir-bone mt-0.5">{{ $jobOrder->diagnosis->identified_issues }}</p>
                            </div>
                            <div>
                                <span class="font-bold text-ir-amber-deep">Recommended Repairs:</span>
                                <p class="text-ir-bone mt-0.5">{{ $jobOrder->diagnosis->recommended_repairs }}</p>
                            </div>
                        </div>

                        @if(!empty($jobOrder->diagnosis->ai_suggestions))
                            <div class="p-4 rounded-md bg-ir-void border border-ir-gold/30 text-xs space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-ir-amber-deep flex items-center gap-1.5">
                                        <i class="fa-solid fa-wand-magic-sparkles text-amber-400"></i> AI Diagnostic Recommendation
                                    </span>
                                    <span class="px-2 py-0.5 rounded bg-ir-amber-deep/20 text-ir-amber-deep text-[10px] font-mono">
                                        Confidence: {{ ($jobOrder->diagnosis->ai_suggestions['confidence'] ?? 0.9) * 100 }}%
                                    </span>
                                </div>
                                <p class="text-ir-bone font-semibold">{{ $jobOrder->diagnosis->ai_suggestions['diagnosis'] ?? '' }}</p>
                                <ul class="list-disc list-inside space-y-1 text-ir-bone">
                                    @foreach($jobOrder->diagnosis->ai_suggestions['recommended_actions'] ?? [] as $act)
                                        <li>{{ $act }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Parts Usage Section -->
            <div class="bg-ir-carbon border border-ir-copper rounded-md p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-ir-copper pb-3">
                    <h4 class="text-sm font-bold text-ir-bone uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-boxes-stacked text-ir-gold"></i> Replacement Parts Used
                    </h4>
                    <span class="text-xs text-ir-bone/70">Auto-deducts inventory upon selection</span>
                </div>

                <!-- Add Part Form -->
                <form action="{{ route('job_orders.add_part', $jobOrder) }}" method="POST" class="flex flex-col sm:flex-row gap-3">
                    @csrf
                    <select name="part_id" required class="flex-1 px-4 py-2.5 rounded-md bg-ir-void border border-ir-copper text-sm text-ir-bone focus:outline-none focus:border-ir-gold">
                        <option value="">-- Choose Replacement Part --</option>
                        @foreach($availableParts as $part)
                            <option value="{{ $part->id }}">
                                {{ $part->name }} (SKU: {{ $part->sku }}) - Stock: {{ $part->stock_quantity }} | Price: ₱{{ number_format($part->selling_price, 2) }}
                            </option>
                        @endforeach
                    </select>

                    <input type="number" name="quantity" value="1" min="1" required class="w-24 px-3 py-2.5 rounded-md bg-ir-void border border-ir-copper text-sm text-ir-bone focus:outline-none text-center">

                    <button type="submit" class="px-4 py-2.5 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone font-semibold text-xs transition-colors">
                        + Add Part
                    </button>
                </form>

                <!-- Parts Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-ir-bone">
                        <thead class="bg-ir-void font-semibold text-ir-bone/70 uppercase tracking-wider border-b border-ir-copper">
                            <tr>
                                <th class="px-4 py-3">Part SKU & Name</th>
                                <th class="px-4 py-3 text-center">Qty</th>
                                <th class="px-4 py-3 text-right">Unit Price</th>
                                <th class="px-4 py-3 text-right">Subtotal</th>
                                <th class="px-4 py-3 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ir-copper">
                            @forelse($jobOrder->parts as $jPart)
                                <tr class="hover:bg-ir-carbon/40">
                                    <td class="px-4 py-3 font-medium text-ir-bone">
                                        {{ $jPart->part?->name }}
                                        <span class="block text-[10px] text-ir-copper">SKU: {{ $jPart->part?->sku }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center font-bold">{{ $jPart->quantity }}</td>
                                    <td class="px-4 py-3 text-right">₱{{ number_format($jPart->unit_price, 2) }}</td>
                                    <td class="px-4 py-3 text-right font-bold text-ir-bone">₱{{ number_format($jPart->total_price, 2) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <form action="{{ route('job_orders.remove_part', [$jobOrder, $jPart]) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-400 hover:text-red-300" title="Remove part and restore stock">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-ir-copper">No parts attached to this repair job yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Before / After Photos Section -->
            <div class="bg-ir-carbon border border-ir-copper rounded-md p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-ir-copper pb-3">
                    <h4 class="text-sm font-bold text-ir-bone uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-camera text-ir-gold"></i> Customer Signatures & Inspection Attachments
                    </h4>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2">
                    @forelse($jobOrder->attachments as $att)
                        <div class="p-2 rounded-md bg-ir-void border border-ir-copper text-center space-y-2 relative group cursor-pointer" @click="openPhotoModal('{{ $att->file_path }}', 'attachment', {{ $att->id }})">
                            @if(str_contains($att->file_path, 'signatures'))
                                <img src="{{ $att->file_path }}" alt="Signature" class="h-20 mx-auto object-contain bg-white rounded p-1">
                                <span class="block text-[10px] font-bold text-teal-400 uppercase">Customer Signature</span>
                            @else
                                <img src="{{ $att->file_path }}" alt="Photo" class="h-24 w-full object-cover rounded-lg group-hover:scale-105 transition-transform">
                                <span class="block text-[10px] font-bold text-ir-amber-deep uppercase">{{ str_replace('_', ' ', $att->type) }}</span>
                                <div class="absolute bottom-6 right-3 bg-ir-carbon/90 text-ir-gold text-[10px] font-bold px-2 py-0.5 rounded-full border border-ir-copper flex items-center gap-1 shadow">
                                    <i class="fa-solid fa-comments"></i> {{ $att->comments->count() }}
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="col-span-4 text-center text-xs text-ir-copper py-4">No attachments uploaded yet.</div>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- Right 1 Column Sidebar -->
        <div class="space-y-6">

            <!-- Cost Summary & Calculator Card -->
            <div class="bg-ir-carbon border border-ir-copper rounded-md p-6 space-y-4">
                <h4 class="text-sm font-bold text-ir-bone uppercase tracking-wider flex items-center justify-between border-b border-ir-copper pb-3">
                    <span><i class="fa-solid fa-calculator text-ir-gold mr-1"></i> Cost Calculation</span>
                    <span class="text-xs text-emerald-400 font-bold">₱{{ number_format($jobOrder->total_cost, 2) }}</span>
                </h4>

                <form action="{{ route('job_orders.update_costs', $jobOrder) }}" method="POST" class="space-y-3 text-xs">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label class="block text-ir-bone/70 mb-1">Labor Cost (₱)</label>
                        <input type="number" step="0.01" name="labor_cost" value="{{ $jobOrder->labor_cost }}" class="w-full px-3 py-2 rounded-md bg-ir-void border border-ir-copper text-ir-bone font-semibold text-right">
                    </div>

                    <div>
                        <label class="block text-ir-bone/70 mb-1">Parts Cost (Auto-pulled) (₱)</label>
                        <input type="text" readonly value="₱{{ number_format($jobOrder->parts_cost, 2) }}" class="w-full px-3 py-2 rounded-md bg-ir-void/60 border border-ir-copper text-ir-bone/70 font-semibold text-right">
                    </div>

                    <div>
                        <label class="block text-ir-bone/70 mb-1">Shop Service Fee (₱)</label>
                        <input type="number" step="0.01" name="service_fee" value="{{ $jobOrder->service_fee }}" class="w-full px-3 py-2 rounded-md bg-ir-void border border-ir-copper text-ir-bone font-semibold text-right">
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-ir-bone/70 mb-1">Discount Type</label>
                            <select name="discount_type" class="w-full px-3 py-2 rounded-md bg-ir-void border border-ir-copper text-ir-bone">
                                <option value="fixed" {{ $jobOrder->discount_type === 'fixed' ? 'selected' : '' }}>Fixed (₱)</option>
                                <option value="percentage" {{ $jobOrder->discount_type === 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-ir-bone/70 mb-1">Discount Val</label>
                            <input type="number" step="0.01" name="discount_value" value="{{ $jobOrder->discount_value }}" class="w-full px-3 py-2 rounded-md bg-ir-void border border-ir-copper text-ir-bone font-semibold text-right">
                        </div>
                    </div>

                    <button type="submit" class="w-full py-2.5 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone font-semibold transition-colors">
                        Recalculate Total Cost
                    </button>
                </form>
            </div>

            <!-- Live Milestone Updates Audit History -->
            <div class="bg-ir-carbon border border-ir-copper rounded-md p-6 space-y-4">
                <h4 class="text-xs font-bold text-ir-bone/70 uppercase tracking-wider flex items-center justify-between border-b border-ir-copper pb-3">
                    <span><i class="fa-solid fa-clock-rotate-left text-ir-gold mr-1"></i> Live Milestone Feed</span>
                    <span class="text-[10px] text-ir-bone/70">{{ $jobOrder->progressUpdates->count() }} updates</span>
                </h4>

                <div class="space-y-4 max-h-96 overflow-y-auto pr-1 text-xs">
                    @forelse($jobOrder->progressUpdates as $up)
                        <div class="p-3.5 rounded-md bg-ir-void border border-ir-copper space-y-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-ir-gold font-mono">{{ $up->percentage }}%</span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-ir-carbon border border-ir-copper text-ir-bone">{{ $up->pipeline_stage }}</span>
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
                                    <p class="text-ir-bone">+₱{{ number_format($up->approvalRequest->additional_cost, 2) }} | +{{ $up->approvalRequest->additional_time_days }} days</p>
                                </div>
                            @endif

                            <!-- Photos -->
                            <div class="grid grid-cols-3 gap-2 pt-1">
                                @foreach($up->photos as $ph)
                                    <div class="relative cursor-pointer overflow-hidden rounded-lg border border-ir-copper group" @click="openPhotoModal('{{ $ph->file_path }}', 'progress_photo', {{ $ph->id }})">
                                        <img src="{{ $ph->thumbnail_path }}" alt="Milestone" class="h-16 w-full object-cover group-hover:scale-105 transition-transform">
                                        <div class="absolute bottom-1 right-1 bg-ir-carbon/90 text-ir-gold text-[9px] font-bold px-1.5 py-0.5 rounded border border-ir-copper flex items-center gap-1 shadow">
                                            <i class="fa-solid fa-comments"></i> {{ $ph->comments->count() }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="flex items-center justify-between text-[10px] text-ir-copper pt-1">
                                <span>By: {{ $up->user?->name ?? 'Staff' }}</span>
                                <span class="{{ $up->is_customer_visible ? 'text-emerald-400' : 'text-ir-copper' }}">
                                    <i class="fa-solid {{ $up->is_customer_visible ? 'fa-eye' : 'fa-eye-slash' }}"></i> {{ $up->is_customer_visible ? 'Customer Visible' : 'Internal Only' }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-ir-copper py-6">No milestone updates posted yet. Use form above to post first update.</div>
                    @endforelse
                </div>
            </div>

            <!-- Customer QR Code Tracking Card -->
            <div class="bg-ir-carbon border border-ir-copper rounded-md p-6 text-center space-y-3">
                <span class="text-xs font-bold text-ir-bone/70 uppercase tracking-wider block">Customer Live Tracking Token</span>
                <div class="p-3 bg-white rounded-md inline-block">
                    <svg class="w-28 h-28 mx-auto" viewBox="0 0 100 100" fill="black">
                        <rect x="10" y="10" width="25" height="25" />
                        <rect x="15" y="15" width="15" height="15" fill="white"/>
                        <rect x="65" y="10" width="25" height="25" />
                        <rect x="70" y="15" width="15" height="15" fill="white"/>
                        <rect x="10" y="65" width="25" height="25" />
                        <rect x="15" y="70" width="15" height="15" fill="white"/>
                        <rect x="45" y="45" width="15" height="15"/>
                        <rect x="40" y="70" width="20" height="20"/>
                        <rect x="70" y="45" width="15" height="35"/>
                    </svg>
                </div>
                <a href="{{ route('track.show', $jobOrder->tracking_token ?? $jobOrder->ticket_number) }}" target="_blank" class="block text-xs text-ir-gold hover:underline">
                    Open Public Live Tracker Page <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                </a>
            </div>

        </div>

    </div>

    <!-- Canvas Signature Capture Modal -->
    <div x-show="openSigModal" class="fixed inset-0 z-50 bg-ir-void/80 backdrop-blur-sm flex items-center justify-center p-4" x-cloak>
        <div class="bg-ir-carbon border border-ir-copper rounded-md p-6 max-w-lg w-full space-y-4">
            <div class="flex items-center justify-between border-b border-ir-copper pb-3">
                <h3 class="text-base font-bold text-ir-bone flex items-center gap-2">
                    <i class="fa-solid fa-signature text-ir-gold"></i> Customer Device Release Signature
                </h3>
                <button @click="openSigModal = false" class="text-ir-bone/70 hover:text-ir-bone"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <p class="text-xs text-ir-bone/70">Please have the customer sign below to acknowledge receipt of the repaired device in satisfactory condition.</p>

            <div class="border-2 border-dashed border-ir-copper rounded-md bg-white p-2">
                <canvas id="sigCanvas" width="440" height="180" class="w-full h-44 cursor-crosshair"></canvas>
            </div>

            <form action="{{ route('job_orders.save_signature', $jobOrder) }}" method="POST" id="sigForm" class="space-y-3">
                @csrf
                <input type="hidden" name="signature_data" id="sigDataInput">

                <div class="flex justify-between items-center pt-2">
                    <button type="button" @click="clearCanvas()" class="px-4 py-2 rounded-md bg-ir-carbon hover:bg-ir-carbon text-ir-bone text-xs font-semibold">
                        Clear Canvas
                    </button>
                    
                    <div class="flex gap-2">
                        <button type="button" @click="openSigModal = false" class="px-4 py-2 rounded-md bg-ir-carbon text-ir-bone text-xs">Cancel</button>
                        <button type="button" @click="submitSignature()" class="px-5 py-2 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone text-xs font-bold transition-colors">
                            Save & Release Device
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Interactive Photo & Comments Modal (Staff Side) -->
    <div x-show="lightboxUrl" class="fixed inset-0 z-50 bg-ir-void/90 backdrop-blur-md flex items-center justify-center p-3 sm:p-6" x-cloak @click.self="closePhotoModal()">
        <div class="bg-ir-carbon border border-ir-copper rounded-xl overflow-hidden max-w-5xl w-full max-h-[92vh] flex flex-col md:flex-row shadow-2xl relative">
            
            <!-- Close Button -->
            <button @click="closePhotoModal()" class="absolute top-3 right-3 z-10 w-9 h-9 rounded-full bg-ir-void/80 hover:bg-ir-void text-ir-bone hover:text-ir-gold border border-ir-copper flex items-center justify-center transition-colors">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>

            <!-- Left / Top: Photo Preview -->
            <div class="w-full md:w-3/5 bg-ir-void flex items-center justify-center p-4 border-b md:border-b-0 md:border-r border-ir-copper relative min-h-[300px] md:min-h-[500px]">
                <img :src="lightboxUrl" class="max-h-[70vh] md:max-h-[85vh] w-auto max-w-full object-contain rounded-md border border-ir-copper/50">
            </div>

            <!-- Right / Bottom: Comments & Customer Communication Feed -->
            <div class="w-full md:w-2/5 flex flex-col h-[50vh] md:h-auto bg-ir-carbon">
                <!-- Modal Header -->
                <div class="p-4 border-b border-ir-copper flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-extrabold text-ir-bone flex items-center gap-2">
                            <i class="fa-solid fa-comments text-ir-gold"></i> Photo Customer Communication
                        </h4>
                        <span class="text-[11px] text-ir-bone/70">Reply to customer questions on photo</span>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-mono font-bold bg-ir-gold/10 text-ir-gold border border-ir-gold/20" x-text="comments.length + ' comments'"></span>
                </div>

                <!-- Comments List (Scrollable) -->
                <div class="flex-1 overflow-y-auto p-4 space-y-4 text-xs">
                    <template x-if="commentsLoading">
                        <div class="text-center py-8 text-ir-copper space-y-2">
                            <i class="fa-solid fa-circle-notch fa-spin text-2xl"></i>
                            <p>Loading photo comments...</p>
                        </div>
                    </template>

                    <template x-if="!commentsLoading && comments.length === 0">
                        <div class="text-center py-8 text-ir-copper space-y-2">
                            <i class="fa-solid fa-comment-dots text-3xl"></i>
                            <p class="font-medium text-ir-bone/70">No comments on this photo yet.</p>
                            <p class="text-[11px]">Post a note or comment for the customer below.</p>
                        </div>
                    </template>

                    <template x-for="cmt in comments" :key="cmt.id">
                        <div class="space-y-2.5">
                            <!-- Parent Comment Card -->
                            <div class="p-3 rounded-lg bg-ir-void border border-ir-copper space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-[10px] uppercase"
                                              :class="cmt.author_type === 'technician' || cmt.author_type === 'staff' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/30'">
                                            <i :class="cmt.author_type === 'technician' || cmt.author_type === 'staff' ? 'fa-solid fa-user-gear' : 'fa-solid fa-user'"></i>
                                        </span>
                                        <span class="font-bold text-ir-bone" x-text="cmt.author_name"></span>
                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-bold tracking-wider uppercase"
                                              :class="cmt.author_type === 'technician' || cmt.author_type === 'staff' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'bg-ir-gold/10 text-ir-gold border border-ir-gold/30'"
                                              x-text="cmt.author_type === 'technician' ? 'Technician' : (cmt.author_type === 'staff' ? 'Staff' : 'Customer')"></span>
                                    </div>
                                    <span class="text-[10px] text-ir-bone/50 font-mono" x-text="formatDate(cmt.created_at)"></span>
                                </div>

                                <p class="text-ir-bone text-xs leading-relaxed pl-1" x-text="cmt.comment"></p>

                                <div class="flex justify-end pt-1">
                                    <button type="button" @click="setReply(cmt.id, cmt.author_name)" class="text-[10px] font-semibold text-ir-gold hover:underline flex items-center gap-1">
                                        <i class="fa-solid fa-reply"></i> Reply to Customer
                                    </button>
                                </div>
                            </div>

                            <!-- Threaded Replies -->
                            <div class="pl-5 space-y-2 border-l-2 border-ir-copper/50">
                                <template x-for="r in (cmt.replies || [])" :key="r.id">
                                    <div class="p-2.5 rounded-lg bg-ir-void/80 border border-ir-copper/40 space-y-1">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-1.5">
                                                <span class="font-bold text-ir-bone text-[11px]" x-text="r.author_name"></span>
                                                <span class="px-1.5 py-0.5 rounded text-[8px] font-bold uppercase"
                                                      :class="r.author_type === 'technician' || r.author_type === 'staff' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'bg-ir-gold/10 text-ir-gold border border-ir-gold/30'"
                                                      x-text="r.author_type === 'technician' ? 'Technician' : (r.author_type === 'staff' ? 'Staff' : 'Customer')"></span>
                                            </div>
                                            <span class="text-[9px] text-ir-bone/50 font-mono" x-text="formatDate(r.created_at)"></span>
                                        </div>
                                        <p class="text-ir-bone text-[11px] leading-relaxed" x-text="r.comment"></p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Input Form -->
                <div class="p-3 border-t border-ir-copper bg-ir-carbon">
                    <div x-show="replyParentId" class="flex items-center justify-between bg-ir-gold/10 border border-ir-gold/30 px-3 py-1.5 rounded-md mb-2 text-[11px]" x-cloak>
                        <span class="text-ir-gold">Replying to <strong x-text="replyAuthorName"></strong></span>
                        <button type="button" @click="cancelReply()" class="text-ir-bone/70 hover:text-ir-bone"><i class="fa-solid fa-xmark"></i></button>
                    </div>

                    <form @submit.prevent="submitStaffComment()" class="space-y-2">
                        <div class="flex items-center gap-2">
                            <input type="text" x-model="commentInput" required placeholder="Type technician reply or comment..." class="flex-1 px-3 py-2 rounded-md bg-ir-void border border-ir-copper text-xs text-ir-bone focus:border-ir-gold focus:outline-none">
                            <button type="submit" :disabled="submittingComment" class="px-4 py-2 rounded-md bg-emerald-600 hover:bg-emerald-500 text-ir-bone font-bold text-xs flex items-center gap-1 transition-colors disabled:opacity-50">
                                <i class="fa-solid" :class="submittingComment ? 'fa-circle-notch fa-spin' : 'fa-paper-plane'"></i>
                                <span>Post Reply</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>

</div>

@push('scripts')
<script>
    function jobOrderWorkspace() {
        return {
            openSigModal: false,
            showApprovalSection: false,
            currentPct: {{ $jobOrder->current_percentage }},
            percentage: {{ $jobOrder->current_percentage }},
            selectedStage: '{{ $jobOrder->status }}',
            stageRanges: @json(\App\Models\JobOrder::STAGE_PERCENTAGE_RANGES),
            canvas: null,
            ctx: null,
            isDrawing: false,

            // Photo Lightbox & Comments State
            lightboxUrl: null,
            activePhotoType: null,
            activePhotoId: null,
            comments: [],
            commentsLoading: false,
            commentInput: '',
            replyParentId: null,
            replyAuthorName: null,
            submittingComment: false,

            get isRework() {
                return parseInt(this.percentage) < parseInt(this.currentPct);
            },
            init() {
                this.$watch('openSigModal', (val) => {
                    if (val) {
                        setTimeout(() => this.setupCanvas(), 100);
                    }
                });
            },
            onStageChange() {
                const range = this.stageRanges[this.selectedStage];
                if (range) {
                    this.percentage = range[0];
                }
            },
            setupCanvas() {
                this.canvas = document.getElementById('sigCanvas');
                if (!this.canvas) return;
                this.ctx = this.canvas.getContext('2d');
                this.ctx.strokeStyle = '#0f172a';
                this.ctx.lineWidth = 3;
                this.ctx.lineCap = 'round';

                this.canvas.addEventListener('mousedown', (e) => {
                    this.isDrawing = true;
                    this.ctx.beginPath();
                    this.ctx.moveTo(e.offsetX, e.offsetY);
                });

                this.canvas.addEventListener('mousemove', (e) => {
                    if (this.isDrawing) {
                        this.ctx.lineTo(e.offsetX, e.offsetY);
                        this.ctx.stroke();
                    }
                });

                this.canvas.addEventListener('mouseup', () => this.isDrawing = false);
            },
            clearCanvas() {
                if (this.ctx && this.canvas) {
                    this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
                }
            },
            submitSignature() {
                if (!this.canvas) return;
                const dataUrl = this.canvas.toDataURL('image/png');
                document.getElementById('sigDataInput').value = dataUrl;
                document.getElementById('sigForm').submit();
            },

            // Photo Comments Methods
            openPhotoModal(url, type, id) {
                this.lightboxUrl = url;
                this.activePhotoType = type;
                this.activePhotoId = id;
                this.commentInput = '';
                this.replyParentId = null;
                this.replyAuthorName = null;
                this.fetchComments();
            },

            closePhotoModal() {
                this.lightboxUrl = null;
                this.activePhotoType = null;
                this.activePhotoId = null;
            },

            fetchComments() {
                if (!this.activePhotoType || !this.activePhotoId) return;
                this.commentsLoading = true;
                fetch(`/photo-comments/${this.activePhotoType}/${this.activePhotoId}`)
                    .then(res => res.json())
                    .then(data => {
                        this.commentsLoading = false;
                        if (data.success) {
                            this.comments = data.comments;
                        }
                    })
                    .catch(() => {
                        this.commentsLoading = false;
                    });
            },

            setReply(id, name) {
                this.replyParentId = id;
                this.replyAuthorName = name;
            },

            cancelReply() {
                this.replyParentId = null;
                this.replyAuthorName = null;
            },

            submitStaffComment() {
                if (!this.commentInput.trim() || this.submittingComment) return;
                this.submittingComment = true;

                const jobOrderId = {{ $jobOrder->id }};

                fetch(`/job_orders/${jobOrderId}/photo-comments`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        photo_type: this.activePhotoType,
                        photo_id: this.activePhotoId,
                        comment: this.commentInput,
                        parent_id: this.replyParentId
                    })
                })
                .then(res => res.json())
                .then(data => {
                    this.submittingComment = false;
                    if (data.success) {
                        this.commentInput = '';
                        this.replyParentId = null;
                        this.replyAuthorName = null;
                        this.fetchComments();
                    } else {
                        alert(data.message || 'Failed to post reply.');
                    }
                })
                .catch(() => {
                    this.submittingComment = false;
                    alert('Error submitting reply. Please try again.');
                });
            },

            formatDate(dateStr) {
                if (!dateStr) return '';
                const d = new Date(dateStr);
                return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
            }
        }
    }
</script>
@endpush
@endsection
