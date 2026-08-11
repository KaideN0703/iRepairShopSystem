@extends('layouts.public')

@section('title', 'Live Repair Status - Ticket #' . $jobOrder->ticket_number)

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="customerLiveTracker()">

    <!-- Ticket Header — Mobile above-fold: ticket + device + ETA in single column -->
    <div class="flex flex-col gap-3 bg-ir-carbon border border-ir-copper p-5 rounded-md">
        <div class="flex items-center gap-3">
            <h1 class="text-xl font-extrabold text-ir-bone">Live Repair Tracker</h1>
            <span class="px-3 py-1 rounded-full text-xs font-mono font-bold bg-ir-amber-deep/10 text-ir-gold border border-ir-gold/30">
                #{{ $jobOrder->ticket_number }}
            </span>
        </div>
        <p class="text-xs text-ir-bone/70">
            Owner: <strong>{{ $jobOrder->customer?->name }}</strong> | Device: <strong>{{ $jobOrder->device?->brand }} {{ $jobOrder->device?->model }}</strong>
        </p>
        <!-- ETA above fold on mobile (Task 5) -->
        <div class="flex items-center gap-2 text-xs">
            <i class="fa-solid fa-clock text-ir-gold"></i>
            <span class="text-ir-bone/70 font-semibold uppercase tracking-wider">Est. Completion:</span>
            <strong class="text-ir-gold font-mono">{{ $jobOrder->estimated_completion_date ? $jobOrder->estimated_completion_date->format('M d, Y') : 'Pending Inspection' }}</strong>
            <span class="text-ir-copper font-semibold" x-text="'~' + remainingHours + ' hours remaining'"></span>
        </div>
    </div>

    <!-- PENDING CUSTOMER APPROVAL REQUEST PROMINENT CARD -->
    @if($jobOrder->pendingApprovalRequest)
        @php
            $pReq = $jobOrder->pendingApprovalRequest;
        @endphp
        <div class="bg-ir-carbon border border-amber-500/60 rounded-md p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-amber-500/30 pb-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-amber-500/20 text-amber-300 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-circle-exclamation"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-ir-bone">Action Required: Customer Approval Requested</h3>
                        <span class="text-xs text-amber-300 font-medium">Technician discovered an additional item requiring your consent</span>
                    </div>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-mono font-bold bg-amber-500/20 text-amber-300 border border-amber-500/40 uppercase">
                    Status: Pending
                </span>
            </div>

            <div class="space-y-2 text-sm text-ir-bone">
                <h4 class="font-bold text-amber-300 text-base">{{ $pReq->title }}</h4>
                <p class="text-xs text-ir-bone bg-ir-void/60 p-3 rounded-md border border-ir-copper">{{ $pReq->description }}</p>
                
                <div class="flex items-center gap-6 pt-1 text-xs">
                    @if($pReq->additional_cost > 0)
                        <div>
                            <span class="text-ir-bone/70">Additional Cost:</span>
                            <strong class="text-emerald-400 font-bold text-sm ml-1">+<x-currency :amount="$pReq->additional_cost" /></strong>
                        </div>
                    @endif
                    @if($pReq->additional_time_days > 0)
                        <div>
                            <span class="text-ir-bone/70">Time Impact:</span>
                            <strong class="text-amber-300 font-bold text-sm ml-1">+{{ $pReq->additional_time_days }} Day(s)</strong>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Approve / Decline Buttons — 44px tap targets, full width on mobile (Task 5) -->
            <div class="pt-3 border-t border-amber-500/30 flex flex-col sm:flex-row items-stretch sm:items-center sm:justify-end gap-3">
                <button type="button" @click="openDeclineModal = true"
                        class="w-full sm:w-auto min-h-[44px] px-5 py-3 rounded-md bg-ir-carbon hover:bg-ir-carbon text-red-400 border border-red-500/30 font-semibold text-sm transition-colors flex items-center justify-center gap-2">
                    <i class="fa-solid fa-xmark"></i> Decline Request
                </button>

                <form action="{{ route('customer.approval.respond', [$jobOrder->tracking_token ?? $jobOrder->ticket_number, $pReq->id]) }}" method="POST" class="w-full sm:w-auto">
                    @csrf
                    <input type="hidden" name="action" value="approve">
                    <button type="submit"
                            class="w-full min-h-[44px] px-6 py-3 rounded-md bg-emerald-600 hover:bg-emerald-500 text-ir-bone font-bold text-sm flex items-center justify-center gap-2 transition-colors">
                        <i class="fa-solid fa-circle-check"></i> Approve Additional Repair
                    </button>
                </form>
            </div>
        </div>
    @endif

    <!-- LIVE ANIMATED PROGRESS BAR -->
    <div class="bg-ir-carbon border border-ir-copper rounded-md p-6 space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-xs font-bold text-ir-bone/70 uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-bolt text-ir-gold"></i> Real-Time Repair Completion Progress
            </h3>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                <span class="text-xs font-mono font-bold text-emerald-400" x-text="currentPercentage + '%'"></span>
            </div>
        </div>

        <!-- Progress Bar Slider -->
        <div class="w-full bg-ir-void h-4 rounded-full overflow-hidden p-1 border border-ir-copper">
            <div class="h-full bg-ir-gold rounded-full transition-all duration-500" :style="'width: ' + currentPercentage + '%'"></div>
        </div>

        <!-- Stages Indicators -->
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-2 text-center text-[11px]">
            @foreach($stages as $index => $stage)
                @php
                    $isPassed = $index <= $currentStageIndex;
                    $isCurrent = $index === $currentStageIndex;
                @endphp
                <div class="relative p-2 rounded-md border {{ $isCurrent ? 'bg-ir-gold/20 text-ir-gold font-bold border-ir-gold' : ($isPassed ? 'bg-ir-carbon text-ir-amber-deep border-ir-copper' : 'bg-ir-void text-ir-copper border-ir-copper') }}">
                    @if($isCurrent)
                        <div class="absolute -top-1.5 left-1/2 -translate-x-1/2 w-0 h-0 border-l-[4px] border-r-[4px] border-b-[5px] border-l-transparent border-r-transparent border-b-ir-gold"></div>
                    @endif
                    <span class="block text-[9px] uppercase tracking-wider mb-0.5 font-mono">Stage {{ $index + 1 }}</span>
                    <span class="block font-semibold truncate">{{ $stage }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <!-- BEFORE / AFTER COMPARISON SLIDER (Unlocked when Completed/Released or photos exist) -->
    @if(($beforePhoto || $afterPhoto) && in_array($jobOrder->status, ['Ready for Pickup', 'Completed', 'Released']))
        <div class="bg-ir-carbon border border-ir-copper rounded-md p-6 space-y-4">
            <h3 class="text-xs font-bold text-ir-bone/70 uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-sliders text-ir-gold"></i> Repair Results Comparison (Before vs After)
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @if($beforePhoto)
                    <div class="p-3 rounded-md bg-ir-void border border-ir-copper space-y-2 text-center relative group">
                        <span class="text-xs font-bold text-amber-400 uppercase">Device Condition at Intake</span>
                        <div class="relative cursor-pointer overflow-hidden rounded-lg border border-ir-copper" @click="openPhotoModal('{{ $beforePhoto->file_path ?? $beforePhoto->thumbnail_path }}', 'attachment', {{ $beforePhoto->id }})">
                            <img src="{{ $beforePhoto->file_path ?? $beforePhoto->thumbnail_path }}" alt="Before Repair" class="h-48 w-full object-cover group-hover:scale-105 transition-transform">
                            <div class="absolute bottom-2 right-2 bg-ir-carbon/90 text-ir-gold text-[11px] font-bold px-2.5 py-1 rounded-full border border-ir-copper flex items-center gap-1.5 shadow-md">
                                <i class="fa-solid fa-comments"></i> {{ $beforePhoto->comments->count() }} Comments
                            </div>
                        </div>
                    </div>
                @endif

                @if($afterPhoto)
                    <div class="p-3 rounded-md bg-ir-void border border-ir-copper space-y-2 text-center relative group">
                        <span class="text-xs font-bold text-emerald-400 uppercase">Completed Repair Condition</span>
                        <div class="relative cursor-pointer overflow-hidden rounded-lg border border-ir-copper" @click="openPhotoModal('{{ $afterPhoto->file_path ?? $afterPhoto->thumbnail_path }}', 'attachment', {{ $afterPhoto->id }})">
                            <img src="{{ $afterPhoto->file_path ?? $afterPhoto->thumbnail_path }}" alt="After Repair" class="h-48 w-full object-cover group-hover:scale-105 transition-transform">
                            <div class="absolute bottom-2 right-2 bg-ir-carbon/90 text-ir-gold text-[11px] font-bold px-2.5 py-1 rounded-full border border-ir-copper flex items-center gap-1.5 shadow-md">
                                <i class="fa-solid fa-comments"></i> {{ $afterPhoto->comments->count() }} Comments
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- CUSTOMER-VISIBLE PHOTO MILESTONE GALLERY & TIMELINE -->
    <div class="bg-ir-carbon border border-ir-copper rounded-md p-6 space-y-5">
        <h3 class="text-sm font-bold text-ir-bone uppercase tracking-wider flex items-center justify-between border-b border-ir-copper pb-3">
            <span><i class="fa-solid fa-images text-ir-gold mr-2"></i> Photo Milestone Feed & Repair Timeline</span>
            <span class="text-xs text-ir-bone/70 font-mono">{{ $jobOrder->customerProgressUpdates->count() }} milestones</span>
        </h3>

        <div class="space-y-6">
            @forelse($jobOrder->customerProgressUpdates as $update)
                <div class="p-5 rounded-md bg-ir-void border border-ir-copper space-y-4 hover:border-ir-copper transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="w-10 h-10 rounded-md bg-ir-gold/20 text-ir-gold font-extrabold font-mono text-sm border border-ir-gold/30 flex items-center justify-center">
                                {{ $update->percentage }}%
                            </span>
                            <div>
                                <h4 class="text-sm font-bold text-ir-bone">{{ $update->pipeline_stage }}</h4>
                                <span class="text-xs text-ir-bone/70">{{ $update->created_at->diffForHumans() }} ({{ $update->created_at->format('M d, Y h:i A') }})</span>
                            </div>
                        </div>

                        @if($update->is_rework)
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-300 border border-amber-500/20">
                                ⚠️ Rework Notice
                            </span>
                        @endif
                    </div>

                    @if($update->is_rework && $update->rework_reason)
                        <div class="p-3 rounded-md bg-amber-950/30 border border-amber-500/30 text-xs text-amber-200">
                            <strong>Note:</strong> {{ $update->rework_reason }}
                        </div>
                    @endif

                    <p class="text-xs text-ir-bone leading-relaxed font-medium">{{ $update->description }}</p>

                    <!-- Milestone Photos -->
                    @if($update->photos->isNotEmpty())
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2">
                            @foreach($update->photos as $photo)
                                <div class="relative group cursor-pointer overflow-hidden rounded-md border border-ir-copper" @click="openPhotoModal('{{ $photo->file_path }}', 'progress_photo', {{ $photo->id }})">
                                    <img src="{{ $photo->thumbnail_path }}" alt="Milestone Photo" class="h-28 w-full object-cover group-hover:scale-105 transition-transform">
                                    <div class="absolute inset-0 bg-ir-void/60 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center text-ir-bone text-xs font-semibold gap-1 p-2 text-center">
                                        <i class="fa-solid fa-comments text-lg text-ir-gold"></i>
                                        <span>Click to view & comment</span>
                                    </div>
                                    <div class="absolute bottom-1.5 right-1.5 bg-ir-carbon/90 text-ir-gold text-[10px] font-bold px-2 py-0.5 rounded-full border border-ir-copper flex items-center gap-1 shadow">
                                        <i class="fa-solid fa-comments"></i> {{ $photo->comments->count() }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-center py-10 text-ir-copper text-xs">
                    <i class="fa-solid fa-clock text-3xl mb-2"></i>
                    <p>Initial inspection completed. Technical staff will post photo milestone updates shortly.</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Decline Request Reason Modal -->
    @if($jobOrder->pendingApprovalRequest)
        <div x-show="openDeclineModal" class="fixed inset-0 z-50 bg-ir-void/80 backdrop-blur-sm flex items-center justify-center p-4" x-cloak>
            <div class="bg-ir-carbon border border-ir-copper rounded-md p-6 max-w-md w-full space-y-4">
                <div class="flex items-center justify-between border-b border-ir-copper pb-3">
                    <h3 class="text-base font-bold text-ir-bone">Decline Repair Request</h3>
                    <button @click="openDeclineModal = false" class="text-ir-bone/70 hover:text-ir-bone"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <form action="{{ route('customer.approval.respond', [$jobOrder->tracking_token ?? $jobOrder->ticket_number, $jobOrder->pendingApprovalRequest->id]) }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="action" value="decline">

                    <div>
                        <label class="block text-xs font-semibold text-ir-bone/70 uppercase mb-1">Optional Reason for Declining</label>
                        <textarea name="response_note" rows="3" placeholder="e.g. Please proceed with original repair only / return device as-is..." class="w-full px-4 py-2.5 rounded-md bg-ir-void border border-ir-copper text-xs text-ir-bone focus:outline-none"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="openDeclineModal = false" class="px-4 py-2 rounded-md bg-ir-carbon text-ir-bone text-xs">Cancel</button>
                        <button type="submit" class="px-5 py-2 rounded-md bg-red-600 hover:bg-red-500 text-ir-bone text-xs font-bold transition-colors">
                            Confirm Decline
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Interactive Photo & Comments Lightbox Modal -->
    <div x-show="lightboxUrl" class="fixed inset-0 z-50 bg-ir-void/90 backdrop-blur-md flex items-center justify-center p-3 sm:p-6" x-cloak @click.self="closePhotoModal()">
        <div class="bg-ir-carbon border border-ir-copper rounded-xl overflow-hidden max-w-5xl w-full max-h-[92vh] flex flex-col md:flex-row shadow-2xl relative">
            
            <!-- Close Button -->
            <button @click="closePhotoModal()" class="absolute top-3 right-3 z-10 w-9 h-9 rounded-full bg-ir-void/80 hover:bg-ir-void text-ir-bone hover:text-ir-gold border border-ir-copper flex items-center justify-center transition-colors">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>

            <!-- Left / Top: Photo View -->
            <div class="w-full md:w-3/5 bg-ir-void flex items-center justify-center p-4 border-b md:border-b-0 md:border-r border-ir-copper relative min-h-[300px] md:min-h-[500px]">
                <img :src="lightboxUrl" class="max-h-[70vh] md:max-h-[85vh] w-auto max-w-full object-contain rounded-md border border-ir-copper/50">
            </div>

            <!-- Right / Bottom: Comments & Technician Communication Feed -->
            <div class="w-full md:w-2/5 flex flex-col h-[50vh] md:h-auto bg-ir-carbon">
                <!-- Modal Header -->
                <div class="p-4 border-b border-ir-copper flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-extrabold text-ir-bone flex items-center gap-2">
                            <i class="fa-solid fa-comments text-ir-gold"></i> Photo Communication
                        </h4>
                        <span class="text-[11px] text-ir-bone/70">Comments & Technician Discussions</span>
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
                            <p class="text-[11px]">Ask a question or leave a note for the technician below.</p>
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
                                        <i class="fa-solid fa-reply"></i> Reply
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
                    <!-- Reply Banner -->
                    <div x-show="replyParentId" class="flex items-center justify-between bg-ir-gold/10 border border-ir-gold/30 px-3 py-1.5 rounded-md mb-2 text-[11px]" x-cloak>
                        <span class="text-ir-gold">Replying to <strong x-text="replyAuthorName"></strong></span>
                        <button type="button" @click="cancelReply()" class="text-ir-bone/70 hover:text-ir-bone"><i class="fa-solid fa-xmark"></i></button>
                    </div>

                    <form @submit.prevent="submitComment()" class="space-y-2">
                        <div class="flex items-center gap-2">
                            <input type="text" x-model="commentInput" required placeholder="Type a comment or ask a question..." class="flex-1 px-3 py-2 rounded-md bg-ir-void border border-ir-copper text-xs text-ir-bone focus:border-ir-gold focus:outline-none">
                            <button type="submit" :disabled="submittingComment" class="px-4 py-2 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone font-bold text-xs flex items-center gap-1 transition-colors disabled:opacity-50">
                                <i class="fa-solid" :class="submittingComment ? 'fa-circle-notch fa-spin' : 'fa-paper-plane'"></i>
                                <span>Send</span>
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
    function customerLiveTracker() {
        return {
            currentPercentage: {{ $jobOrder->current_percentage }},
            remainingHours: {{ $remainingHours }},
            openDeclineModal: false,
            lightboxUrl: null,
            activePhotoType: null,
            activePhotoId: null,
            comments: [],
            commentsLoading: false,
            commentInput: '',
            replyParentId: null,
            replyAuthorName: null,
            submittingComment: false,

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

            submitComment() {
                if (!this.commentInput.trim() || this.submittingComment) return;
                this.submittingComment = true;

                const token = "{{ $jobOrder->tracking_token ?? $jobOrder->ticket_number }}";

                fetch(`/track/${token}/photo-comments`, {
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
                        alert(data.message || 'Failed to post comment.');
                    }
                })
                .catch(() => {
                    this.submittingComment = false;
                    alert('Error submitting comment. Please try again.');
                });
            },

            formatDate(dateStr) {
                if (!dateStr) return '';
                const d = new Date(dateStr);
                return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
            },

            init() {
                // Alpine polling every 10s to fetch live updates without page refresh
                setInterval(() => {
                    fetch("{{ route('track.progress_updates', $jobOrder->tracking_token ?? $jobOrder->ticket_number) }}")
                        .then(res => res.json())
                        .then(data => {
                            if (data.current_percentage !== undefined) {
                                this.currentPercentage = data.current_percentage;
                            }
                        })
                        .catch(() => {});
                }, 10000);
            }
        }
    }
</script>
@endpush
@endsection
