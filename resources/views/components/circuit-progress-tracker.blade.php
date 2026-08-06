{{--
    Circuit Progress Tracker Component
    ======================================
    Props:
      $stages — array of stage arrays, each with:
        - label     (string)  : stage name
        - done      (bool)    : has this stage been completed
        - active    (bool)    : is this the current/active stage
        - tooltip   (string)  : optional description for hover tooltip
        - photo_url (string)  : optional photo URL for hover tooltip

    Usage:
      <x-circuit-progress-tracker :stages="$stages" />
--}}
@props(['stages' => []])

<div class="circuit-tracker-wrap">
    <div class="circuit-tracker" role="list" aria-label="Repair progress stages">

        @foreach ($stages as $i => $stage)
            @php
                $done   = $stage['done']   ?? false;
                $active = $stage['active'] ?? false;
                $label  = $stage['label']  ?? '';
                $tip    = $stage['tooltip'] ?? null;
                $photo  = $stage['photo_url'] ?? null;
            @endphp

            {{-- Stage node --}}
            <div class="circuit-stage" role="listitem" aria-label="{{ $label }}">
                {{-- Node circle --}}
                <div class="circuit-node {{ $done ? 'done' : ($active ? 'active' : '') }}"
                     aria-current="{{ $active ? 'step' : 'false' }}">
                </div>

                {{-- Label --}}
                <span class="circuit-stage-label {{ $done ? 'done' : ($active ? 'active' : '') }}">
                    {{ $label }}
                </span>

                {{-- Tooltip (hover) --}}
                @if($tip || $photo)
                    <div class="circuit-stage-tooltip" role="tooltip">
                        @if($photo)
                            <img src="{{ $photo }}" alt="{{ $label }} photo"
                                 style="width:100%; border-radius:4px; margin-bottom:0.4rem; border:1px solid #7A4A12;">
                        @endif
                        @if($tip)
                            <p style="margin:0; font-size:0.72rem; color:#EDE6D6; line-height:1.4;">{{ $tip }}</p>
                        @endif
                        <div style="margin-top:0.35rem; font-family:'JetBrains Mono',monospace; font-size:0.6rem; color:#7A4A12;">
                            {{ strtoupper($label) }}
                        </div>
                    </div>
                @endif
            </div>

            {{-- Connector line between nodes --}}
            @if (!$loop->last)
                <div class="circuit-connector {{ $done ? 'done' : '' }}" aria-hidden="true"></div>
            @endif
        @endforeach

    </div>
</div>
