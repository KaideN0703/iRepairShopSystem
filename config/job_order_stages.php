<?php

/**
 * iRepair Shop System — Job Order Stage Configuration
 * =====================================================
 * Single source of truth for the 8 repair pipeline stages.
 * Every view (staff, public, invoice) must reference this config.
 *
 * Keys:
 *   label        — Human-readable stage name (canonical)
 *   badge_class  — CSS class(es) appended to .badge for coloring
 *   color_token  — Design-token CSS variable name (for reference)
 *   percentage   — [min, max] progress % range for this stage
 *   description  — Short description displayed in tooltips / tracker
 */

return [
    'stages' => [
        'Received' => [
            'label'       => 'Received',
            'badge_class' => 'badge-received',
            'color_token' => '--ir-amber-deep',
            'percentage'  => [0, 10],
            'description' => 'Device has been received and logged into the system.',
        ],
        'Diagnosing' => [
            'label'       => 'Diagnosing',
            'badge_class' => 'badge-diagnosing',
            'color_token' => '--ir-gold',
            'percentage'  => [10, 25],
            'description' => 'Technician is performing inspection and identifying issues.',
        ],
        'Waiting for Parts' => [
            'label'       => 'Waiting for Parts',
            'badge_class' => 'badge-waiting',
            'color_token' => '--ir-bone',
            'percentage'  => [25, 40],
            'description' => 'Awaiting replacement parts or components.',
        ],
        'Under Repair' => [
            'label'       => 'Under Repair',
            'badge_class' => 'badge-under-repair',
            'color_token' => '--ir-gold',
            'percentage'  => [40, 75],
            'description' => 'Active repair work in progress by technician.',
        ],
        'Testing' => [
            'label'       => 'Testing',
            'badge_class' => 'badge-testing',
            'color_token' => '--ir-amber-deep',
            'percentage'  => [75, 90],
            'description' => 'Quality assurance and function testing.',
        ],
        'Ready for Pickup' => [
            'label'       => 'Ready for Pickup',
            'badge_class' => 'badge-ready',
            'color_token' => '--ir-signal-green',
            'percentage'  => [90, 95],
            'description' => 'Repair complete. Device is ready for customer pickup.',
        ],
        'Completed' => [
            'label'       => 'Completed',
            'badge_class' => 'badge-completed',
            'color_token' => '--ir-signal-green',
            'percentage'  => [95, 100],
            'description' => 'All work finalized and documented.',
        ],
        'Released' => [
            'label'       => 'Released',
            'badge_class' => 'badge-released',
            'color_token' => '--ir-bone',
            'percentage'  => [100, 100],
            'description' => 'Device released to customer with signature.',
        ],
    ],
];
