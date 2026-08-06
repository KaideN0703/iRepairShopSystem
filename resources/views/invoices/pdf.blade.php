<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Official Receipt {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; }
        .header { border-bottom: 2px solid #334155; padding-bottom: 10px; margin-bottom: 20px; }
        .logo { font-size: 20px; font-weight: bold; color: #4338ca; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background: #f1f5f9; padding: 6px; text-align: left; font-size: 10px; text-transform: uppercase; border-bottom: 1px solid #cbd5e1; }
        td { padding: 8px 6px; border-bottom: 1px solid #e2e8f0; }
        .text-right { text-align: right; }
        .total-box { width: 220px; float: right; margin-top: 15px; }
        .total-row { display: table-row; }
        .total-cell { display: table-cell; padding: 3px 0; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">iRepairShop Management</div>
        <div>Official Repair Invoice & Receipt</div>
        <div style="margin-top: 5px;"><strong>Invoice #:</strong> {{ $invoice->invoice_number }} | <strong>Date:</strong> {{ $invoice->issue_date ? $invoice->issue_date->format('Y-m-d') : '' }}</div>
    </div>

    <div>
        <strong>Customer:</strong> {{ $invoice->customer?->name }} ({{ $invoice->customer?->phone }})<br>
        <strong>Job Ticket:</strong> #{{ $invoice->jobOrder?->ticket_number }} - {{ $invoice->jobOrder?->device?->brand }} {{ $invoice->jobOrder?->device?->model }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Price</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">₱{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">₱{{ number_format($item->total_price, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-box">
        <div style="font-size: 14px; font-weight: bold; margin-top: 5px;">
            Total: ₱{{ number_format($invoice->total_amount, 2) }}
        </div>
        <div style="font-size: 13px; color: #059669;">
            Paid: ₱{{ number_format($invoice->paid_amount, 2) }}
        </div>
        <div style="font-size: 11px; text-transform: uppercase; margin-top: 5px;">
            Payment Status: {{ $invoice->payment_status }}
        </div>
    </div>
</body>
</html>
