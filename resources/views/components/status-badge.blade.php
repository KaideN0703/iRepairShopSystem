{{--
    Status Badge Component
    ======================
    Renders a stage badge using config/job_order_stages.php as the
    single source of truth for labels and colors.

    Usage:
        <x-status-badge :stage="$jobOrder->status" />
        <x-status-badge stage="Under Repair" />
        <x-status-badge :stage="$invoice->payment_status" />  ← for payment statuses too
--}}
@props(['stage' => ''])

@php
    $stages = config('job_order_stages.stages', []);
    $stageConfig = $stages[$stage] ?? null;

    // Payment status fallback mappings
    $paymentBadgeMap = [
        'paid'    => 'badge-paid',
        'unpaid'  => 'badge-unpaid',
        'partial' => 'badge-partial',
    ];

    if ($stageConfig) {
        $badgeClass = $stageConfig['badge_class'];
        $label      = $stageConfig['label'];
    } elseif (isset($paymentBadgeMap[strtolower($stage)])) {
        $badgeClass = $paymentBadgeMap[strtolower($stage)];
        $label      = ucfirst($stage);
    } else {
        $badgeClass = 'badge-bone';
        $label      = $stage;
    }
@endphp

<span class="badge {{ $badgeClass }}">{{ $label }}</span>
