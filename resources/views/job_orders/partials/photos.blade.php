{{-- Photos & Signatures Tab Partial --}}
{{-- Variables inherited: $jobOrder --}}
{{-- Uses Alpine openPhotoModal() from parent workspace --}}

<div class="space-y-6">

    {{-- Upload Before / After Photo --}}
    <div class="bg-ir-void border border-ir-copper rounded-md p-5 space-y-4">
        <h4 class="text-xs font-bold text-ir-bone/70 uppercase tracking-wider border-b border-ir-copper pb-3">
            <i class="fa-solid fa-camera-retro text-ir-gold mr-1"></i> Upload Device Condition Photos
        </h4>
        <form action="{{ route('job_orders.upload_photo', $jobOrder) }}" method="POST"
              enctype="multipart/form-data" class="flex flex-col sm:flex-row gap-3">
            @csrf
            <select name="type" required class="ir-select sm:w-48">
                <option value="photo_before">Photo: Before Repair</option>
                <option value="photo_after">Photo: After Repair</option>
            </select>
            <input type="file" name="photo" accept="image/*" required
                   class="flex-1 text-xs text-ir-bone/70 file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-ir-gold file:text-ir-bone hover:file:bg-ir-amber-deep cursor-pointer">
            <button type="submit" class="btn-primary btn-sm whitespace-nowrap">
                <i class="fa-solid fa-upload"></i> Upload
            </button>
        </form>
    </div>

    {{-- Attachments Gallery --}}
    <div class="bg-ir-void border border-ir-copper rounded-md p-5 space-y-3">
        <h4 class="text-xs font-bold text-ir-bone/70 uppercase tracking-wider border-b border-ir-copper pb-3">
            <i class="fa-solid fa-images text-ir-gold mr-1"></i> Inspection Photos & Customer Signatures
        </h4>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2">
            @forelse($jobOrder->attachments as $att)
                <div class="p-2 rounded-md bg-ir-carbon border border-ir-copper text-center space-y-2 relative group cursor-pointer"
                     @click="openPhotoModal('{{ $att->file_path }}', 'attachment', {{ $att->id }})">
                    @if(str_contains($att->file_path, 'signatures'))
                        <img src="{{ $att->file_path }}" alt="Signature" class="h-20 mx-auto object-contain bg-white rounded p-1">
                        <span class="block text-[10px] font-bold text-ir-signal-green uppercase">Customer Signature</span>
                    @else
                        <img src="{{ $att->file_path }}" alt="Photo" class="h-24 w-full object-cover rounded-lg group-hover:scale-105 transition-transform">
                        <span class="block text-[10px] font-bold text-ir-amber-deep uppercase">{{ str_replace('_', ' ', $att->type) }}</span>
                        <div class="absolute bottom-6 right-3 bg-ir-carbon/90 text-ir-gold text-[10px] font-bold px-2 py-0.5 rounded-full border border-ir-copper flex items-center gap-1 shadow">
                            <i class="fa-solid fa-comments"></i> {{ $att->comments->count() }}
                        </div>
                    @endif
                </div>
            @empty
                <div class="col-span-4 text-center py-8">
                    <div class="max-w-xs mx-auto space-y-2">
                        <div class="w-10 h-10 rounded-full bg-ir-carbon border border-ir-copper text-ir-gold flex items-center justify-center mx-auto text-base">
                            <i class="fa-solid fa-camera"></i>
                        </div>
                        <p class="text-xs font-semibold text-ir-bone/70">No device photos or signatures attached yet</p>
                        <p class="text-[11px] text-ir-bone/50">Upload intake/repair photos using the form above to document hardware condition.</p>
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Signature Capture (Task 8 — with typed fallback) --}}
    <div class="bg-ir-void border border-ir-copper rounded-md p-5 space-y-4"
         x-data="{ sigMode: 'canvas' }">
        <h4 class="text-xs font-bold text-ir-bone/70 uppercase tracking-wider border-b border-ir-copper pb-3">
            <i class="fa-solid fa-signature text-ir-gold mr-1"></i> Customer Release Signature
        </h4>

        <p class="text-xs text-ir-bone/70">Have the customer sign below to acknowledge receipt of their repaired device.</p>

        {{-- Mode toggle --}}
        <div class="flex gap-2 text-xs">
            <button type="button" @click="sigMode = 'canvas'"
                    :class="sigMode === 'canvas' ? 'text-ir-gold border-ir-gold bg-ir-gold/10' : 'text-ir-bone/60 border-ir-copper'"
                    class="px-3 py-1.5 rounded border transition-colors">
                <i class="fa-solid fa-pen"></i> Draw Signature
            </button>
            <button type="button" @click="sigMode = 'typed'"
                    :class="sigMode === 'typed' ? 'text-ir-gold border-ir-gold bg-ir-gold/10' : 'text-ir-bone/60 border-ir-copper'"
                    class="px-3 py-1.5 rounded border transition-colors">
                <i class="fa-solid fa-keyboard"></i> Can't sign? Type name instead
            </button>
        </div>

        {{-- Canvas signature --}}
        <div x-show="sigMode === 'canvas'">
            <div class="border-2 border-dashed border-ir-copper rounded-md bg-white p-2">
                <canvas id="sigCanvas" width="500" height="180" class="w-full h-44 cursor-crosshair"></canvas>
            </div>
            <form action="{{ route('job_orders.save_signature', $jobOrder) }}" method="POST" id="sigForm" class="mt-3">
                @csrf
                <input type="hidden" name="signature_data" id="sigDataInput">
                <input type="hidden" name="signature_type" value="drawn">
                <div class="flex justify-between items-center">
                    <button type="button" onclick="clearCanvas()" class="btn-secondary btn-sm">Clear</button>
                    <button type="button" onclick="submitSignature()" class="btn-primary">
                        <i class="fa-solid fa-check"></i> Save & Release Device
                    </button>
                </div>
            </form>
        </div>

        {{-- Typed signature fallback (Task 8) --}}
        <div x-show="sigMode === 'typed'">
            <form action="{{ route('job_orders.save_signature', $jobOrder) }}" method="POST" id="typedSigForm" class="space-y-3">
                @csrf
                <input type="hidden" name="signature_type" value="typed">
                <div>
                    <label class="ir-label">Full Name (acts as digital signature)</label>
                    <input type="text" name="typed_signature" required
                           placeholder="Customer types their full name here..."
                           class="ir-input font-mono text-lg tracking-widest">
                    <p class="text-[11px] text-ir-copper mt-1">
                        By typing your full name, you acknowledge receipt of the repaired device in satisfactory condition.
                    </p>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="btn-primary">
                        <i class="fa-solid fa-check"></i> Confirm & Release Device
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
