<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Official Receipt - {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 13px; color: #0f172a; padding: 20px; line-height: 1.5; }
        .invoice-box { max-width: 700px; margin: auto; border: 1px solid #e2e8f0; padding: 30px; border-radius: 8px; }
        .title { font-size: 24px; font-weight: bold; color: #4338ca; }
        .flex-between { display: flex; justify-content: space-between; margin-bottom: 20px; border-bottom: 2px solid #0f172a; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: #f1f5f9; padding: 8px; text-align: left; border-bottom: 1px solid #cbd5e1; }
        td { padding: 8px; border-bottom: 1px solid #e2e8f0; }
        .text-right { text-align: right; }
        .grand-total { font-size: 18px; font-weight: bold; color: #4f46e5; border-top: 2px solid #0f172a; padding-top: 10px; }
        @media print { button { display: none; } body { padding: 0; } .invoice-box { border: none; } }
    </style>
</head>
<body>

    <div style="text-align: center; margin-bottom: 15px;">
        <button onclick="window.print()" style="padding: 8px 16px; font-weight: bold; background: #4f46e5; color: white; border: none; border-radius: 6px; cursor: pointer;">🖨️ Print Official Receipt</button>
    </div>

    <div class="invoice-box">
        <div class="flex-between">
            <div>
                <div class="title">iRepairShop</div>
                <div>Official Business Receipt</div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 18px; font-weight: bold;">{{ $invoice->invoice_number }}</div>
                <div>Date: {{ $invoice->issue_date ? $invoice->issue_date->format('M d, Y') : '' }}</div>
                <div>Status: <strong style="text-transform: uppercase;">{{ $invoice->payment_status }}</strong></div>
            </div>
        </div>

        <div style="margin-bottom: 20px;">
            <strong>Billed To:</strong> {{ $invoice->customer?->name }} ({{ $invoice->customer?->phone }})<br>
            <strong>Job Ticket:</strong> #{{ $invoice->jobOrder?->ticket_number }} - {{ $invoice->jobOrder?->device?->brand }} {{ $invoice->jobOrder?->device?->model }}
        </div>

        <table>
            <thead>
                <tr>
                    <th>Item Description</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Unit Price</th>
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

        <div style="width: 250px; margin-left: auto;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                <span>Subtotal:</span>
                <span>₱{{ number_format($invoice->subtotal, 2) }}</span>
            </div>
            @if($invoice->discount_amount > 0)
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px; color: #059669;">
                    <span>Discount:</span>
                    <span>-₱{{ number_format($invoice->discount_amount, 2) }}</span>
                </div>
            @endif
            <div class="grand-total" style="display: flex; justify-content: space-between;">
                <span>Total Paid:</span>
                <span>₱{{ number_format($invoice->paid_amount, 2) }}</span>
            </div>
        </div>
    </div>

</body>
</html>
