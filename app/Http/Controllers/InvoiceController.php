<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\JobOrder;
use App\Models\Payment;
use App\Models\AuditLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $search = $request->query('search');

        $invoices = Invoice::with(['jobOrder.device', 'customer', 'payments'])
            ->when($status, function ($q, $status) {
                $q->where('payment_status', $status);
            })
            ->when($search, function ($q, $search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('jobOrder', function ($jq) use ($search) {
                        $jq->where('ticket_number', 'like', "%{$search}%");
                    });
            })
            ->latest()
            ->paginate(15);

        return view('invoices.index', compact('invoices', 'status', 'search'));
    }

    public function generateFromJob(JobOrder $jobOrder)
    {
        if ($jobOrder->invoice) {
            return redirect()->route('invoices.show', $jobOrder->invoice)->with('info', 'Invoice already exists for this job order.');
        }

        $latestInv = Invoice::latest('id')->first();
        $nextId = $latestInv ? $latestInv->id + 1 : 1;
        $invNumber = 'INV-' . date('Y') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        $invoice = Invoice::create([
            'invoice_number' => $invNumber,
            'job_order_id' => $jobOrder->id,
            'customer_id' => $jobOrder->customer_id,
            'issue_date' => now(),
            'due_date' => now()->addDays(7),
            'subtotal' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 0,
            'paid_amount' => 0,
            'payment_status' => 'unpaid',
        ]);

        $subtotal = 0;

        // 1. Labor Item
        if ($jobOrder->labor_cost > 0) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'item_type' => 'labor',
                'description' => "Repair Labor Fee ({$jobOrder->reported_issue})",
                'quantity' => 1,
                'unit_price' => $jobOrder->labor_cost,
                'total_price' => $jobOrder->labor_cost,
            ]);
            $subtotal += $jobOrder->labor_cost;
        }

        // 2. Parts Items
        foreach ($jobOrder->parts as $jPart) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'item_type' => 'part',
                'description' => $jPart->part?->name ?? 'Replacement Part',
                'quantity' => $jPart->quantity,
                'unit_price' => $jPart->unit_price,
                'total_price' => $jPart->total_price,
            ]);
            $subtotal += $jPart->total_price;
        }

        // 3. Service Fee Item
        if ($jobOrder->service_fee > 0) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'item_type' => 'service_fee',
                'description' => 'Diagnostic & Shop Service Charge',
                'quantity' => 1,
                'unit_price' => $jobOrder->service_fee,
                'total_price' => $jobOrder->service_fee,
            ]);
            $subtotal += $jobOrder->service_fee;
        }

        // Discount calculation
        $discount = 0;
        if ($jobOrder->discount_type === 'percentage') {
            $discount = ($subtotal * $jobOrder->discount_value) / 100;
        } else {
            $discount = $jobOrder->discount_value;
        }

        $total = max(0, $subtotal - $discount);

        $invoice->update([
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'total_amount' => $total,
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name,
            'action' => 'create',
            'module' => 'Invoices',
            'description' => "Generated invoice {$invNumber} for ticket #{$jobOrder->ticket_number}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return redirect()->route('invoices.show', $invoice)->with('success', "Invoice {$invNumber} generated successfully!");
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['jobOrder.device', 'customer', 'items', 'payments.user']);
        return view('invoices.show', compact('invoice'));
    }

    public function recordPayment(Request $request, Invoice $invoice)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|in:Cash,Credit Card,GCash,Bank Transfer',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $latestPay = Payment::latest('id')->first();
        $nextId = $latestPay ? $latestPay->id + 1 : 1;
        $payNum = 'PAY-' . date('Y') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        Payment::create([
            'payment_number' => $payNum,
            'invoice_id' => $invoice->id,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'payment_date' => now(),
            'reference_number' => $request->reference_number,
            'user_id' => Auth::id(),
            'notes' => $request->notes,
        ]);

        $invoice->updatePaymentStatus();

        AuditLog::create([
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name,
            'action' => 'process_payments',
            'module' => 'Invoices',
            'description' => "Recorded payment of \${$request->amount} for Invoice {$invoice->invoice_number}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', "Payment {$payNum} recorded successfully!");
    }

    public function printReceipt(Invoice $invoice)
    {
        $invoice->load(['jobOrder.device', 'customer', 'items', 'payments.user']);
        return view('invoices.receipt', compact('invoice'));
    }

    public function downloadPdf(Invoice $invoice)
    {
        $invoice->load(['jobOrder.device', 'customer', 'items', 'payments.user']);
        $pdf = Pdf::loadView('invoices.pdf', compact('invoice'));
        return $pdf->download("Official_Receipt_{$invoice->invoice_number}.pdf");
    }
}
