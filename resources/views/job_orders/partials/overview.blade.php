{{-- Overview Tab Partial --}}
{{-- Variables inherited: $jobOrder, $technicians, $stages --}}

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

    {{-- Customer Card --}}
    <div class="bg-ir-void border border-ir-copper rounded-md p-5">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold text-ir-bone/70 uppercase tracking-wider">
                <i class="fa-solid fa-user text-ir-gold mr-1"></i> Customer Profile
            </span>
            <a href="{{ route('customers.show', $jobOrder->customer_id) }}" class="text-xs text-ir-gold hover:underline">
                View History
            </a>
        </div>
        <h3 class="text-base font-bold text-ir-bone">{{ $jobOrder->customer?->name }}</h3>
        <div class="text-xs text-ir-bone space-y-1 mt-2">
            <p><i class="fa-solid fa-id-card text-ir-copper w-4"></i> Code: {{ $jobOrder->customer?->customer_code }}</p>
            <p><i class="fa-solid fa-phone text-ir-copper w-4"></i> {{ $jobOrder->customer?->phone }}</p>
            <p><i class="fa-solid fa-envelope text-ir-copper w-4"></i> {{ $jobOrder->customer?->email ?? 'No email' }}</p>
            <p><i class="fa-solid fa-location-dot text-ir-copper w-4"></i> {{ $jobOrder->customer?->address ?? 'No address' }}</p>
        </div>
    </div>

    {{-- Device Card --}}
    <div class="bg-ir-void border border-ir-copper rounded-md p-5">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold text-ir-bone/70 uppercase tracking-wider">
                <i class="fa-solid fa-mobile-screen text-ir-gold mr-1"></i> Device Specifications
            </span>
            <span class="text-xs text-ir-bone/70 font-mono">S/N: {{ $jobOrder->device?->serial_number ?? 'N/A' }}</span>
        </div>
        <h3 class="text-base font-bold text-ir-bone">{{ $jobOrder->device?->brand }} {{ $jobOrder->device?->model }}</h3>
        <div class="text-xs text-ir-bone space-y-1 mt-2">
            <p><i class="fa-solid fa-microchip text-ir-copper w-4"></i> Type: {{ $jobOrder->device?->device_type }}</p>
            <p><i class="fa-solid fa-palette text-ir-copper w-4"></i> Color: {{ $jobOrder->device?->color ?? 'Unspecified' }}</p>
            <p><i class="fa-solid fa-key text-ir-copper w-4"></i> Passcode: <strong class="text-ir-gold font-mono">{{ $jobOrder->device?->passcode_pattern ?? 'None' }}</strong></p>
        </div>
    </div>

</div>

{{-- Reported Issue --}}
<div class="bg-ir-void border border-ir-copper rounded-md p-5">
    <span class="block text-xs font-semibold text-ir-bone/70 uppercase tracking-wider mb-2">
        <i class="fa-solid fa-comment-medical text-ir-gold mr-1"></i> Customer Reported Symptoms
    </span>
    <p class="text-sm text-ir-bone font-medium">{{ $jobOrder->reported_issue }}</p>
    @if($jobOrder->customer_notes)
        <div class="mt-3 pt-3 border-t border-ir-copper/40">
            <span class="block text-xs font-semibold text-ir-bone/70 uppercase tracking-wider mb-1">Customer Notes</span>
            <p class="text-xs text-ir-bone">{{ $jobOrder->customer_notes }}</p>
        </div>
    @endif
</div>

{{-- Assign Technician & Internal Notes --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

    {{-- Assign Technician --}}
    <div class="bg-ir-void border border-ir-copper rounded-md p-5">
        <h4 class="text-xs font-bold text-ir-bone/70 uppercase tracking-wider mb-3">
            <i class="fa-solid fa-user-gear text-ir-gold mr-1"></i> Assigned Technician
        </h4>
        <form action="{{ route('job_orders.assign_technician', $jobOrder) }}" method="POST" class="flex gap-2">
            @csrf
            <select name="technician_id" class="ir-select flex-1 text-sm">
                <option value="">-- Unassigned --</option>
                @foreach($technicians as $tech)
                    <option value="{{ $tech->id }}" {{ $jobOrder->technician_id == $tech->id ? 'selected' : '' }}>
                        {{ $tech->name }} ({{ $tech->specialty }}) · {{ $tech->active_jobs_count }} active
                    </option>
                @endforeach
            </select>
            <button type="submit" class="btn-primary btn-sm">Assign</button>
        </form>
    </div>

    {{-- Internal Notes --}}
    <div class="bg-ir-void border border-ir-copper rounded-md p-5">
        <h4 class="text-xs font-bold text-ir-bone/70 uppercase tracking-wider mb-3">
            <i class="fa-solid fa-lock text-ir-copper mr-1"></i> Internal Notes
            <span class="ml-1 text-[10px] text-ir-copper font-normal normal-case">(Not visible to customer)</span>
        </h4>
        <p class="text-xs text-ir-bone">{{ $jobOrder->internal_notes ?? '— None recorded —' }}</p>
    </div>

</div>

{{-- Status History Timeline --}}
@if($jobOrder->statusHistories->count())
<div class="bg-ir-void border border-ir-copper rounded-md p-5">
    <h4 class="text-xs font-bold text-ir-bone/70 uppercase tracking-wider mb-3 flex items-center gap-2">
        <i class="fa-solid fa-timeline text-ir-gold"></i> Status Change History
    </h4>
    <div class="space-y-2 max-h-48 overflow-y-auto">
        @foreach($jobOrder->statusHistories as $hist)
            <div class="flex items-start gap-3 text-xs">
                <div class="w-1.5 h-1.5 rounded-full bg-ir-gold mt-1.5 shrink-0"></div>
                <div>
                    <span class="text-ir-bone/70">{{ $hist->created_at->format('M d, Y h:i A') }}</span>
                    <span class="text-ir-bone font-semibold ml-1">{{ $hist->status_from ?? 'New' }} → {{ $hist->status_to }}</span>
                    @if($hist->remarks)
                        <span class="text-ir-copper ml-1">· {{ $hist->remarks }}</span>
                    @endif
                    @if($hist->user)
                        <span class="text-ir-bone/50 ml-1">by {{ $hist->user->name }}</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif

{{-- Customer QR Tracking Token --}}
<div class="bg-ir-void border border-ir-copper rounded-md p-5 text-center space-y-3">
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
    <p class="text-[10px] text-ir-copper font-mono">{{ $jobOrder->tracking_token ?? $jobOrder->ticket_number }}</p>
</div>
