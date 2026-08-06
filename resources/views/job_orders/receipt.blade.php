<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Repair Receipt - Ticket #{{ $jobOrder->ticket_number }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 13px; color: #1e293b; line-height: 1.5; margin: 0; padding: 20px; }
        .receipt-card { max-w: 750px; margin: 0 auto; border: 1px solid #cbd5e1; padding: 30px; border-radius: 8px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #0f172a; padding-bottom: 15px; margin-bottom: 20px; }
        .logo-title { font-size: 22px; font-weight: bold; color: #4338ca; }
        .subtitle { font-size: 12px; color: #64748b; }
        .ticket-box { text-align: right; }
        .ticket-num { font-size: 18px; font-weight: bold; color: #0f172a; }
        .grid-2 { display: flex; gap: 20px; margin-bottom: 20px; }
        .col { flex: 1; bg: #f8fafc; padding: 12px; border-radius: 6px; border: 1px solid #e2e8f0; }
        .col h4 { margin: 0 0 8px 0; font-size: 11px; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: #f1f5f9; text-align: left; padding: 8px 12px; font-size: 11px; text-transform: uppercase; color: #475569; border-bottom: 1px solid #cbd5e1; }
        td { padding: 10px 12px; border-bottom: 1px solid #e2e8f0; }
        .text-right { text-align: right; }
        .totals { width: 280px; margin-left: auto; font-size: 13px; }
        .totals div { display: flex; justify-content: space-between; padding: 4px 0; }
        .total-grand { font-size: 16px; font-weight: bold; border-top: 2px solid #0f172a; padding-top: 8px; color: #0f172a; }
        .terms { margin-top: 30px; border-top: 1px dashed #cbd5e1; padding-top: 15px; font-size: 11px; color: #64748b; }
        .btn-print { background: #4f46e5; color: white; border: none; padding: 8px 16px; font-size: 13px; font-weight: bold; border-radius: 6px; cursor: pointer; margin-bottom: 20px; }
        @media print { .btn-print { display: none; } body { padding: 0; } .receipt-card { border: none; padding: 0; } }
    </style>
</head>
<body>

    <div style="text-align: center;">
        <button onclick="window.print()" class="btn-print">🖨️ Print Repair Receipt</button>
    </div>

    <div class="receipt-card">
        <div class="header">
            <div>
                <div class="logo-title">iRepairShop Business Solutions</div>
                <div class="subtitle">100 Tech Blvd, Suite 400 | Phone: +1 (800) 555-REPAIR</div>
                <div class="subtitle">Email: support@irepairshop.com | Web: www.irepairshop.com</div>
            </div>
            <div class="ticket-box">
                <div class="ticket-num">REPAIR TICKET</div>
                <div style="font-size: 16px; font-weight: bold; color: #4f46e5;">#{{ $jobOrder->ticket_number }}</div>
                <div class="subtitle">Date: {{ $jobOrder->created_at->format('M d, Y') }}</div>
            </div>
        </div>

        <div class="grid-2">
            <div class="col">
                <h4>Customer Information</h4>
                <strong>{{ $jobOrder->customer?->name }}</strong><br>
                Phone: {{ $jobOrder->customer?->phone }}<br>
                Email: {{ $jobOrder->customer?->email ?? 'N/A' }}<br>
                Customer ID: {{ $jobOrder->customer?->customer_code }}
            </div>
            <div class="col">
                <h4>Device Details</h4>
                <strong>{{ $jobOrder->device?->brand }} {{ $jobOrder->device?->model }}</strong><br>
                Serial No: {{ $jobOrder->device?->serial_number ?? 'N/A' }}<br>
                Color: {{ $jobOrder->device?->color ?? 'N/A' }}<br>
                Est. Completion: <strong>{{ $jobOrder->estimated_completion_date ? $jobOrder->estimated_completion_date->format('M d, Y') : 'N/A' }}</strong>
            </div>
        </div>

        <div style="background: #f8fafc; padding: 12px; border-radius: 6px; border: 1px solid #e2e8f0; margin-bottom: 20px;">
            <h4 style="margin: 0 0 4px 0; font-size: 11px; text-transform: uppercase; color: #64748b;">Reported Issue / Symptoms</h4>
            <div>{{ $jobOrder->reported_issue }}</div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Item / Description</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Unit Cost</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @if($jobOrder->labor_cost > 0)
                    <tr>
                        <td>Repair Labor Charge</td>
                        <td class="text-right">1</td>
                        <td class="text-right">₱{{ number_format($jobOrder->labor_cost, 2) }}</td>
                        <td class="text-right">₱{{ number_format($jobOrder->labor_cost, 2) }}</td>
                    </tr>
                @endif
                @foreach($jobOrder->parts as $jPart)
                    <tr>
                        <td>{{ $jPart->part?->name }} (SKU: {{ $jPart->part?->sku }})</td>
                        <td class="text-right">{{ $jPart->quantity }}</td>
                        <td class="text-right">₱{{ number_format($jPart->unit_price, 2) }}</td>
                        <td class="text-right">₱{{ number_format($jPart->total_price, 2) }}</td>
                    </tr>
                @endforeach
                @if($jobOrder->service_fee > 0)
                    <tr>
                        <td>Diagnostic / Shop Service Fee</td>
                        <td class="text-right">1</td>
                        <td class="text-right">₱{{ number_format($jobOrder->service_fee, 2) }}</td>
                        <td class="text-right">₱{{ number_format($jobOrder->service_fee, 2) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>

        <div class="totals">
            <div>
                <span>Estimated Subtotal:</span>
                <span>₱{{ number_format($jobOrder->labor_cost + $jobOrder->parts_cost + $jobOrder->service_fee, 2) }}</span>
            </div>
            @if($jobOrder->discount_value > 0)
                <div>
                    <span>Discount:</span>
                    <span>-₱{{ number_format($jobOrder->discount_type === 'percentage' ? ($jobOrder->labor_cost + $jobOrder->parts_cost + $jobOrder->service_fee) * $jobOrder->discount_value / 100 : $jobOrder->discount_value, 2) }}</span>
                </div>
            @endif
            <div class="total-grand">
                <span>Estimated Total:</span>
                <span>₱{{ number_format($jobOrder->total_cost, 2) }}</span>
            </div>
        </div>

        <div class="terms">
            <strong>Terms & Warranty Conditions:</strong>
            <p style="margin: 4px 0 0 0;">
                1. Devices left uncollected over 60 days post-completion notice will be considered abandoned.<br>
                2. Standard 90-Day warranty covers replaced parts & labor against defects. Liquid damaged devices carry no extended warranty.<br>
                3. Scan the QR code or track your repair live online at: <u>{{ route('status.show', $jobOrder->ticket_number) }}</u>
            </p>
        </div>
    </div>

</body>
</html>
