{{--
    Stock Badge Component
    ======================
    Renders a unified, visually refined stock level badge for inventory items.

    Usage:
        <x-stock-badge :part="$part" />
        <x-stock-badge :quantity="$part->stock_quantity" :reorder-level="$part->reorder_level" />
--}}
@props([
    'part' => null,
    'quantity' => null,
    'reorderLevel' => null,
    'showReorder' => false,
])

@php
    $qty = $quantity ?? ($part ? $part->stock_quantity : 0);
    $reorder = $reorderLevel ?? ($part ? $part->reorder_level : 5);

    if ($qty <= 0) {
        $badgeClass = 'bg-rose-500/15 text-rose-400 border-rose-500/40';
        $iconClass = 'fa-solid fa-circle-xmark';
    } elseif ($qty <= $reorder) {
        $badgeClass = 'bg-amber-500/15 text-amber-400 border-amber-500/40';
        $iconClass = 'fa-solid fa-triangle-exclamation';
    } else {
        $badgeClass = 'bg-emerald-500/15 text-emerald-400 border-emerald-500/40';
        $iconClass = 'fa-solid fa-circle-check';
    }
@endphp

<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border whitespace-nowrap align-middle shadow-sm {{ $badgeClass }}">
    <i class="{{ $iconClass }} text-[10px]"></i>
    <span>
        @if($qty <= 0)
            0 left (Out of Stock)
        @elseif($qty <= $reorder)
            {{ $qty }} left (Low Stock)
        @else
            {{ $qty }} units
        @endif
        @if($showReorder && $qty <= $reorder)
            <span class="opacity-75 font-mono text-[10px] ml-0.5">(Min: {{ $reorder }})</span>
        @endif
    </span>
</span>
