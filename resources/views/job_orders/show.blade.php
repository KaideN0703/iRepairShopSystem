@extends('layouts.app')

@section('title', 'Ticket #' . $jobOrder->ticket_number)

@section('content')
<div class="space-y-5" x-data="jobOrderWorkspace()">

    {{-- ============================================================
         TICKET HEADER BAR
         ============================================================ --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-ir-carbon border border-ir-copper p-5 rounded-md">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="font-display font-bold text-xl uppercase tracking-wide text-ir-bone">
                    Ticket #{{ $jobOrder->ticket_number }}
                </h2>
                <x-status-badge :stage="$jobOrder->status" />
                <span class="badge badge-copper font-mono">{{ $jobOrder->current_percentage }}%</span>

                @if($jobOrder->pendingApprovalRequest)
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/40 flex items-center gap-1.5">
                        <i class="fa-solid fa-clock-rotate-left"></i> Approval Pending
                    </span>
                @endif

                @if(in_array($jobOrder->status, ['Awaiting Staff Decision']))
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-red-500/20 text-red-300 border border-red-500/40 flex items-center gap-1.5">
                        <i class="fa-solid fa-flag"></i> Awaiting Staff Decision
                    </span>
                @endif

                <span class="text-xs text-ir-gold font-semibold px-2 py-0.5 rounded bg-ir-gold/10 border border-ir-gold/20">
                    {{ $jobOrder->priority }} Priority
                </span>
            </div>
            <p class="text-xs text-ir-bone/60 mt-1">
                {{ $jobOrder->device?->brand }} {{ $jobOrder->device?->model }} · {{ $jobOrder->customer?->name }}
                · Created {{ $jobOrder->created_at->format('M d, Y') }}
                @if($jobOrder->estimated_completion_date)
                    · ETA {{ $jobOrder->estimated_completion_date->format('M d, Y') }}
                @endif
            </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('track.show', $jobOrder->tracking_token ?? $jobOrder->ticket_number) }}" target="_blank"
               class="btn-secondary btn-sm">
                <i class="fa-solid fa-eye"></i> Customer Tracker
            </a>
            <a href="{{ route('job_orders.receipt', $jobOrder) }}" target="_blank" class="btn-secondary btn-sm">
                <i class="fa-solid fa-print"></i> Print Receipt
            </a>
        </div>
    </div>

    {{-- ============================================================
         PROGRESS BAR (compact, always visible)
         ============================================================ --}}
    <div class="bg-ir-carbon border border-ir-copper rounded-md px-5 py-4 space-y-3">
        <div class="flex items-center justify-between text-xs">
            <span class="font-bold text-ir-bone/70 uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-route text-ir-gold"></i> Repair Progress
            </span>
            <div class="flex items-center gap-3">
                {{-- Quick Stage Change Dropdown --}}
                <form action="{{ route('job_orders.update_status', $jobOrder) }}" method="POST" class="flex items-center gap-2">
                    @csrf
                    @method('PATCH')
                    <select name="status" class="ir-select py-1 text-xs pr-6 w-auto">
                        @foreach($stages as $stage)
                            <option value="{{ $stage }}" {{ $jobOrder->status === $stage ? 'selected' : '' }}>{{ $stage }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn-primary btn-sm">Update Stage</button>
                </form>
            </div>
        </div>

        {{-- Progress Bar with stage tracker --}}
        <div class="w-full bg-ir-void h-2.5 rounded-full overflow-hidden border border-ir-copper">
            <div class="h-full bg-ir-gold rounded-full transition-all duration-700"
                 style="width: {{ $jobOrder->current_percentage }}%"></div>
        </div>

        {{-- Stage indicators with Task 7 accessible labels --}}
        <div class="grid grid-cols-4 sm:grid-cols-8 gap-1 text-center">
            @foreach($stages as $idx => $stage)
                @php
                    $stageConfig = config('job_order_stages.stages.' . $stage, []);
                    $isPassed = array_search($jobOrder->status, $stages) > $idx;
                    $isCurrent = $jobOrder->status === $stage;
                @endphp
                <div class="relative py-1.5 px-1 rounded text-[9px] leading-tight
                    {{ $isCurrent ? 'bg-ir-gold/20 border border-ir-gold text-ir-gold' : ($isPassed ? 'text-ir-amber-deep' : 'text-ir-copper') }}">
                    @if($isCurrent)
                        {{-- "You are here" marker (Task 7) --}}
                        <div class="absolute -top-2 left-1/2 -translate-x-1/2 w-0 h-0 border-l-[5px] border-r-[5px] border-b-[6px] border-l-transparent border-r-transparent border-b-ir-gold"></div>
                    @endif
                    <div class="font-mono font-bold mb-0.5 text-[8px]">{{ $idx + 1 }}</div>
                    <div class="font-semibold uppercase tracking-tight leading-none">{{ str_replace(' ', chr(10), $stage) }}</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ============================================================
         TABBED WORKSPACE (Task 1)
         ============================================================ --}}
    <div class="bg-ir-carbon border border-ir-copper rounded-md overflow-hidden">

        {{-- Tab Navigation Bar --}}
        <nav class="flex overflow-x-auto border-b border-ir-copper bg-ir-void" role="tablist">
            @php
                $tabs = [
                    ['id' => 'overview',  'icon' => 'fa-house',            'label' => 'Overview'],
                    ['id' => 'diagnosis', 'icon' => 'fa-stethoscope',      'label' => 'Diagnosis'],
                    ['id' => 'progress',  'icon' => 'fa-route',            'label' => 'Progress',
                        'alert' => $jobOrder->progressUpdates->count() . ' updates'],
                    ['id' => 'photos',    'icon' => 'fa-camera',           'label' => 'Photos'],
                    ['id' => 'approval',  'icon' => 'fa-file-circle-check','label' => 'Approval',
                        'dot' => $jobOrder->pendingApprovalRequest ? 'red' : null],
                    ['id' => 'invoice',   'icon' => 'fa-file-invoice-dollar','label' => 'Invoice',
                        'dot' => ($jobOrder->invoice && $jobOrder->invoice->payment_status !== 'paid') ? 'red' : null],
                    ['id' => 'warranty',  'icon' => 'fa-shield-halved',    'label' => 'Warranty'],
                ];
            @endphp

            @foreach($tabs as $t)
                <button
                    @click="tab = '{{ $t['id'] }}'"
                    :class="tab === '{{ $t['id'] }}'
                        ? 'text-ir-gold border-b-2 border-ir-gold bg-ir-carbon'
                        : 'text-ir-bone/60 hover:text-ir-bone hover:bg-ir-carbon/40'"
                    class="relative flex items-center gap-1.5 px-4 py-3 text-xs font-semibold whitespace-nowrap transition-colors shrink-0 focus:outline-none"
                    role="tab"
                    :aria-selected="tab === '{{ $t['id'] }}'"
                    aria-controls="tab-{{ $t['id'] }}"
                >
                    <i class="fa-solid {{ $t['icon'] }} text-[11px]"></i>
                    <span>{{ $t['label'] }}</span>
                    {{-- Alert dot --}}
                    @if(isset($t['dot']) && $t['dot'] === 'red')
                        <span class="absolute top-2 right-2 w-2 h-2 rounded-full bg-ir-alert"></span>
                    @endif
                    {{-- Count badge --}}
                    @if(isset($t['alert']))
                        <span class="text-[9px] font-mono px-1 py-0.5 rounded bg-ir-copper/20 text-ir-copper">{{ $t['alert'] }}</span>
                    @endif
                </button>
            @endforeach
        </nav>

        {{-- Tab Panels --}}
        <div class="p-5">

            <div id="tab-overview"  x-show="tab === 'overview'"  role="tabpanel" x-cloak>
                @include('job_orders.partials.overview')
            </div>

            <div id="tab-diagnosis" x-show="tab === 'diagnosis'" role="tabpanel" x-cloak>
                @include('job_orders.partials.diagnosis')
            </div>

            <div id="tab-progress"  x-show="tab === 'progress'"  role="tabpanel" x-cloak>
                @include('job_orders.partials.progress')
            </div>

            <div id="tab-photos"    x-show="tab === 'photos'"    role="tabpanel" x-cloak>
                @include('job_orders.partials.photos')
            </div>

            <div id="tab-approval"  x-show="tab === 'approval'"  role="tabpanel" x-cloak>
                @include('job_orders.partials.approval')
            </div>

            <div id="tab-invoice"   x-show="tab === 'invoice'"   role="tabpanel" x-cloak>
                @include('job_orders.partials.invoice')
            </div>

            <div id="tab-warranty"  x-show="tab === 'warranty'"  role="tabpanel" x-cloak>
                @include('job_orders.partials.warranty')
            </div>

        </div>
    </div>

    {{-- ============================================================
         PHOTO COMMENTS LIGHTBOX MODAL (shared across tabs)
         ============================================================ --}}
    <div x-show="lightboxUrl" class="fixed inset-0 z-50 bg-ir-void/90 backdrop-blur-md flex items-center justify-center p-3 sm:p-6"
         x-cloak @click.self="closePhotoModal()">
        <div class="bg-ir-carbon border border-ir-copper rounded-xl overflow-hidden max-w-5xl w-full max-h-[92vh] flex flex-col md:flex-row shadow-2xl relative">

            <button @click="closePhotoModal()"
                    class="absolute top-3 right-3 z-10 w-9 h-9 rounded-full bg-ir-void/80 hover:bg-ir-void text-ir-bone hover:text-ir-gold border border-ir-copper flex items-center justify-center transition-colors">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>

            {{-- Photo Preview --}}
            <div class="w-full md:w-3/5 bg-ir-void flex items-center justify-center p-4 border-b md:border-b-0 md:border-r border-ir-copper min-h-[300px] md:min-h-[500px]">
                <img :src="lightboxUrl" class="max-h-[70vh] md:max-h-[85vh] w-auto max-w-full object-contain rounded-md border border-ir-copper/50">
            </div>

            {{-- Comments Panel --}}
            <div class="w-full md:w-2/5 flex flex-col h-[50vh] md:h-auto bg-ir-carbon">
                <div class="p-4 border-b border-ir-copper flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-extrabold text-ir-bone flex items-center gap-2">
                            <i class="fa-solid fa-comments text-ir-gold"></i> Photo Communication
                        </h4>
                        <span class="text-[11px] text-ir-bone/70">Reply to customer questions on this photo</span>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-mono font-bold bg-ir-gold/10 text-ir-gold border border-ir-gold/20" x-text="comments.length + ' comments'"></span>
                </div>

                <div class="flex-1 overflow-y-auto p-4 space-y-4 text-xs">
                    <template x-if="commentsLoading">
                        <div class="text-center py-8 text-ir-copper space-y-2">
                            <i class="fa-solid fa-circle-notch fa-spin text-2xl"></i>
                            <p>Loading comments...</p>
                        </div>
                    </template>
                    <template x-if="!commentsLoading && comments.length === 0">
                        <div class="text-center py-8 text-ir-copper space-y-2">
                            <i class="fa-solid fa-comment-dots text-3xl"></i>
                            <p class="font-medium text-ir-bone/70">No comments on this photo yet.</p>
                        </div>
                    </template>
                    <template x-for="cmt in comments" :key="cmt.id">
                        <div class="space-y-2.5">
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
                                    <button type="button" @click="setReply(cmt.id, cmt.author_name)"
                                            class="text-[10px] font-semibold text-ir-gold hover:underline flex items-center gap-1">
                                        <i class="fa-solid fa-reply"></i> Reply to Customer
                                    </button>
                                </div>
                            </div>
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

                {{-- Comment Input --}}
                <div class="p-3 border-t border-ir-copper bg-ir-carbon">
                    <div x-show="replyParentId" class="flex items-center justify-between bg-ir-gold/10 border border-ir-gold/30 px-3 py-1.5 rounded-md mb-2 text-[11px]" x-cloak>
                        <span class="text-ir-gold">Replying to <strong x-text="replyAuthorName"></strong></span>
                        <button type="button" @click="cancelReply()" class="text-ir-bone/70 hover:text-ir-bone"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                    <form @submit.prevent="submitComment()" class="flex gap-2">
                        <input type="text" x-model="commentInput" required
                               placeholder="Type a staff comment or reply..."
                               class="flex-1 ir-input text-xs">
                        <button type="submit" :disabled="submittingComment" class="btn-primary btn-sm">
                            <i class="fa-solid" :class="submittingComment ? 'fa-circle-notch fa-spin' : 'fa-paper-plane'"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function jobOrderWorkspace() {
    return {
        // Tab state — reads from URL ?tab= query param for deep-linking
        tab: new URLSearchParams(window.location.search).get('tab') || 'overview',

        // Progress update form state
        selectedStage: '{{ $jobOrder->status }}',
        percentage: {{ $jobOrder->current_percentage }},
        showApprovalSection: false,

        get isRework() {
            return this.percentage < {{ $jobOrder->current_percentage }};
        },

        onStageChange() {
            // Suggest mid-point of stage range when stage is changed
            const ranges = @json(\App\Models\JobOrder::STAGE_PERCENTAGE_RANGES);
            const range = ranges[this.selectedStage];
            if (range) {
                this.percentage = Math.round((range[0] + range[1]) / 2);
            }
        },

        // Photo lightbox state
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
                .then(r => r.json())
                .then(d => {
                    this.commentsLoading = false;
                    if (d.success) this.comments = d.comments;
                })
                .catch(() => { this.commentsLoading = false; });
        },

        setReply(id, name) { this.replyParentId = id; this.replyAuthorName = name; },
        cancelReply() { this.replyParentId = null; this.replyAuthorName = null; },

        submitComment() {
            if (!this.commentInput.trim() || this.submittingComment) return;
            this.submittingComment = true;
            const jobId = {{ $jobOrder->id }};
            fetch(`/job_orders/${jobId}/photo-comments`, {
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
            .then(r => r.json())
            .then(d => {
                this.submittingComment = false;
                if (d.success) {
                    this.commentInput = '';
                    this.replyParentId = null;
                    this.replyAuthorName = null;
                    this.fetchComments();
                } else {
                    alert(d.message || 'Failed to post comment.');
                }
            })
            .catch(() => { this.submittingComment = false; alert('Error submitting comment.'); });
        },

        formatDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
        },

        init() {
            // Update URL when tab changes (deep-linking support per Task 1 spec)
            this.$watch('tab', (val) => {
                const url = new URL(window.location.href);
                url.searchParams.set('tab', val);
                window.history.replaceState({}, '', url);
            });
        }
    };
}

// Canvas signature handling
const canvas = document.getElementById('sigCanvas');
if (canvas) {
    const ctx = canvas.getContext('2d');
    let drawing = false;
    canvas.addEventListener('mousedown', () => drawing = true);
    canvas.addEventListener('mouseup', () => drawing = false);
    canvas.addEventListener('mouseleave', () => drawing = false);
    canvas.addEventListener('mousemove', e => {
        if (!drawing) return;
        const r = canvas.getBoundingClientRect();
        ctx.beginPath();
        ctx.arc(e.clientX - r.left, e.clientY - r.top, 1.5, 0, Math.PI * 2);
        ctx.fillStyle = '#0B0B0C';
        ctx.fill();
    });
    canvas.addEventListener('touchstart', e => { e.preventDefault(); drawing = true; }, { passive: false });
    canvas.addEventListener('touchend', () => drawing = false);
    canvas.addEventListener('touchmove', e => {
        e.preventDefault();
        if (!drawing) return;
        const r = canvas.getBoundingClientRect();
        const t = e.touches[0];
        ctx.beginPath();
        ctx.arc(t.clientX - r.left, t.clientY - r.top, 1.5, 0, Math.PI * 2);
        ctx.fillStyle = '#0B0B0C';
        ctx.fill();
    }, { passive: false });
}

function clearCanvas() {
    const c = document.getElementById('sigCanvas');
    if (c) c.getContext('2d').clearRect(0, 0, c.width, c.height);
}

function submitSignature() {
    const c = document.getElementById('sigCanvas');
    const input = document.getElementById('sigDataInput');
    if (!c || !input) return;
    const blank = document.createElement('canvas');
    blank.width = c.width; blank.height = c.height;
    if (c.toDataURL() === blank.toDataURL()) {
        alert('Please provide a signature before saving.');
        return;
    }
    input.value = c.toDataURL('image/png');
    document.getElementById('sigForm').submit();
}
</script>
@endpush
