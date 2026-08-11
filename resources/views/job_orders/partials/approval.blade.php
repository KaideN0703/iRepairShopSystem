{{-- Approval Tab Partial --}}
{{-- Variables inherited: $jobOrder --}}
{{-- Task 3: Declined approval resolution path --}}

<div class="space-y-5" x-data="{ declineConfirm: false }">

    {{-- Pending Approval Request --}}
    @if($jobOrder->pendingApprovalRequest)
        @php $pReq = $jobOrder->pendingApprovalRequest; @endphp
        <div class="bg-amber-950/20 border border-amber-500/50 rounded-md p-5 space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-amber-500/20 text-amber-300 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-circle-exclamation"></i>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-ir-bone">Action Required: Customer Approval Pending</h4>
                    <span class="text-xs text-amber-300">Awaiting customer decision before repair can proceed</span>
                </div>
                <span class="ml-auto px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-amber-500/20 text-amber-300 border border-amber-500/40">Pending</span>
            </div>

            <div class="bg-ir-void rounded-md p-4 space-y-2">
                <h5 class="font-bold text-amber-300">{{ $pReq->title }}</h5>
                <p class="text-xs text-ir-bone">{{ $pReq->description }}</p>
                <div class="flex gap-4 text-xs pt-1">
                    @if($pReq->additional_cost > 0)
                    <span class="text-ir-bone/70">Additional Cost: <strong class="text-ir-gold"><x-currency :amount="$pReq->additional_cost" /></strong></span>
                    @endif
                    @if($pReq->additional_time_days > 0)
                    <span class="text-ir-bone/70">Time Impact: <strong class="text-amber-300">+{{ $pReq->additional_time_days }} day(s)</strong></span>
                    @endif
                </div>
                <p class="text-[10px] text-ir-copper">Sent on {{ $pReq->created_at->format('M d, Y h:i A') }}</p>
            </div>

            <div class="text-xs text-ir-bone/70 italic">
                <i class="fa-solid fa-info-circle text-ir-copper mr-1"></i>
                Waiting for customer to approve or decline via their tracking link.
            </div>
        </div>
    @endif

    {{-- All Approval Requests History --}}
    <div class="bg-ir-void border border-ir-copper rounded-md p-5 space-y-4">
        <h4 class="text-xs font-bold text-ir-bone/70 uppercase tracking-wider border-b border-ir-copper pb-3">
            <i class="fa-solid fa-file-circle-check text-ir-gold mr-1"></i>
            Approval Request History ({{ $jobOrder->approvalRequests->count() }})
        </h4>

        <div class="space-y-4">
            @forelse($jobOrder->approvalRequests as $req)
                <div class="p-4 rounded-md bg-ir-carbon border border-ir-copper space-y-3">
                    <div class="flex items-center justify-between">
                        <strong class="text-sm text-ir-bone">{{ $req->title }}</strong>
                        <span class="px-2.5 py-0.5 rounded font-bold uppercase text-[10px]
                            @if($req->status === 'approved') bg-emerald-500/20 text-emerald-300 border border-emerald-500/30
                            @elseif($req->status === 'declined') bg-red-500/20 text-red-300 border border-red-500/30
                            @elseif($req->status === 'expired') bg-ir-copper/20 text-ir-copper border border-ir-copper/30
                            @else bg-amber-500/20 text-amber-300 border border-amber-500/30 @endif">
                            {{ $req->status }}
                        </span>
                    </div>
                    <p class="text-xs text-ir-bone/80">{{ $req->description }}</p>
                    <div class="flex gap-4 text-xs text-ir-bone/70">
                        <span>Additional: <strong class="text-ir-gold font-mono"><x-currency :amount="$req->additional_cost ?? 0" /></strong></span>
                        <span>Time: <strong class="text-amber-300">+{{ $req->additional_time_days ?? 0 }} day(s)</strong></span>
                    </div>

                    @if($req->response_note)
                    <div class="text-xs bg-ir-void p-2 rounded border border-ir-copper/40">
                        <span class="text-ir-bone/60">Customer note:</span> {{ $req->response_note }}
                    </div>
                    @endif

                    @if($req->responded_at)
                    <p class="text-[10px] text-ir-copper">Responded: {{ $req->responded_at->format('M d, Y h:i A') }}</p>
                    @endif

                    {{-- Task 3: Declined Resolution Path --}}
                    @if($req->status === 'declined')
                    <div class="border-t border-red-500/30 pt-3 space-y-2">
                        <p class="text-xs font-bold text-red-300 flex items-center gap-2">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            Customer declined. Staff action required before further progress:
                        </p>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <form action="{{ route('job_orders.resolve_declined', [$jobOrder, $req]) }}" method="POST" class="flex-1">
                                @csrf
                                <input type="hidden" name="resolution" value="proceed_original">
                                <button type="submit" class="w-full py-2 px-3 rounded-md bg-ir-carbon border border-ir-gold text-ir-gold text-xs font-semibold hover:bg-ir-gold/10 transition-colors">
                                    <i class="fa-solid fa-play"></i> Proceed with Original Scope
                                </button>
                            </form>
                            <form action="{{ route('job_orders.resolve_declined', [$jobOrder, $req]) }}" method="POST" class="flex-1">
                                @csrf
                                <input type="hidden" name="resolution" value="return_device">
                                <div class="flex gap-1">
                                    <input type="text" name="staff_note" required placeholder="Staff note (required)..." class="ir-input text-xs flex-1 rounded-r-none">
                                    <button type="submit" class="py-2 px-3 rounded-md rounded-l-none bg-ir-carbon border border-amber-500/50 text-amber-300 text-xs font-semibold hover:bg-amber-500/10 transition-colors whitespace-nowrap">
                                        <i class="fa-solid fa-arrow-right-from-bracket"></i> Return Device As-Is
                                    </button>
                                </div>
                            </form>
                            <form action="{{ route('job_orders.resolve_declined', [$jobOrder, $req]) }}" method="POST" class="flex-1">
                                @csrf
                                <input type="hidden" name="resolution" value="escalate_manager">
                                <button type="submit" class="w-full py-2 px-3 rounded-md bg-ir-carbon border border-red-500/50 text-red-300 text-xs font-semibold hover:bg-red-500/10 transition-colors">
                                    <i class="fa-solid fa-flag"></i> Escalate to Manager
                                </button>
                            </form>
                        </div>
                    </div>
                    @endif
                </div>
            @empty
                <div class="text-center py-8 text-ir-copper text-xs">
                    <i class="fa-solid fa-file-circle-check text-3xl mb-2"></i>
                    <p>No customer approval requests on this ticket.</p>
                </div>
            @endforelse
        </div>
    </div>

</div>
