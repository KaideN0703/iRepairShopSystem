{{--
    Currency Component
    ==================
    Renders a Philippine Peso amount in JetBrains Mono with ₱ prefix.

    Usage:
        <x-currency :amount="$jobOrder->total_cost" />
        <x-currency :amount="500" />
--}}
@props(['amount' => 0])
<span class="font-mono">₱{{ number_format((float) $amount, 2) }}</span>
