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

    Task 7 improvements:
    - Persistent labels always visible (not just on hover)
    - "You are here" chevron arrow above active stage node
    - Contrast-safe colors for amber/copper on dark background
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
            <div class="circuit-stage {{ $active ? 'circuit-stage--active' : '' }}" role="listitem"
                 aria-label="{{ $label }}{{ $active ? ' — Current Stage' : ($done ? ' — Completed' : '') }}">

                {{-- "You are here" chevron marker (Task 7) --}}
                @if($active)
                    <div class="circuit-you-are-here" aria-hidden="true" title="You are here">
                        <svg width="10" height="7" viewBox="0 0 10 7" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M5 7L0.669873 0.25L9.33013 0.25L5 7Z" fill="#F5A623"/>
                        </svg>
                    </div>
                @endif

                {{-- Node circle --}}
                <div class="circuit-node {{ $done ? 'done' : ($active ? 'active' : '') }}"
                     aria-current="{{ $active ? 'step' : 'false' }}">
                </div>

                {{-- Persistent Label (always visible — Task 7) --}}
                <span class="circuit-stage-label {{ $done ? 'done' : ($active ? 'active' : '') }}">
                    {{ $label }}
                </span>

                {{-- Tooltip (hover for additional detail) --}}
                @if($tip || $photo)
                    <div class="circuit-stage-tooltip" role="tooltip">
                        @if($photo)
                            <img src="{{ $photo }}" alt="{{ $label }} photo"
                                 style="width:100%; border-radius:4px; margin-bottom:0.4rem; border:1px solid #7A4A12;">
                        @endif
                        @if($tip)
                            <p style="margin:0; font-size:0.72rem; color:#EDE6D6; line-height:1.4;">{{ $tip }}</p>
                        @endif
                        <div style="margin-top:0.35rem; font-family:'JetBrains Mono',monospace; font-size:0.6rem; color:#B97A1A;">
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
