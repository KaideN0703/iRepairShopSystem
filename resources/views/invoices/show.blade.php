@extends('layouts.app')

@section('title', 'Invoice ' . $invoice->invoice_number)

@section('content')
<div class="space-y-6" x-data="{ openPayModal: false }">

    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-ir-carbon border border-ir-copper p-6 rounded-md">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="text-2xl font-bold text-ir-bone">Invoice #{{ $invoice->invoice_number }}</h2>
                <x-status-badge :stage="$invoice->payment_status" />
            </div>
            <p class="text-xs text-ir-bone/70 mt-1">
                Issued to: <strong>{{ $invoice->customer?->name }}</strong> | Ticket: <a href="{{ route('job_orders.show', $invoice->job_order_id) }}" class="text-ir-gold underline">#{{ $invoice->jobOrder?->ticket_number }}</a>
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('invoices.receipt', $invoice) }}" target="_blank" class="px-4 py-2 rounded-md bg-ir-carbon hover:bg-ir-carbon text-ir-bone text-xs font-semibold">
                <i class="fa-solid fa-print mr-1"></i> Print View
            </a>
            <a href="{{ route('invoices.pdf', $invoice) }}" class="px-4 py-2 rounded-md bg-ir-carbon hover:bg-ir-carbon text-ir-bone text-xs font-semibold">
                <i class="fa-solid fa-file-pdf mr-1 text-red-400"></i> Download PDF
            </a>
            @if($invoice->payment_status !== 'paid')
                <button @click="openPayModal = true" class="px-5 py-2.5 rounded-md bg-emerald-600 hover:bg-emerald-500 text-ir-bone text-xs font-bold transition-colors">
                    <i class="fa-solid fa-cash-register mr-1"></i> Record Payment
                </button>
            @endif
        </div>
    </div>

    <!-- Invoice Details & Items -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Line Items (2 cols) -->
        <div class="lg:col-span-2 bg-ir-carbon border border-ir-copper rounded-md p-6 space-y-4">
            <h4 class="text-xs font-bold text-ir-bone/70 uppercase tracking-wider border-b border-ir-copper pb-3">
                <i class="fa-solid fa-list text-ir-gold mr-1"></i> Itemized Billing Charges
            </h4>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-ir-bone">
                    <thead class="bg-ir-void font-semibold text-ir-bone/70 uppercase border-b border-ir-copper">
                        <tr>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Description</th>
                            <th class="px-4 py-3 text-center">Qty</th>
                            <th class="px-4 py-3 text-right">Unit Price</th>
                            <th class="px-4 py-3 text-right">Total Price</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ir-copper">
                        @foreach($invoice->items as $item)
                            <tr class="hover:bg-ir-carbon/40">
                                <td class="px-4 py-3 capitalize font-semibold text-ir-gold">{{ str_replace('_', ' ', $item->item_type) }}</td>
                                <td class="px-4 py-3 font-medium text-ir-bone">{{ $item->description }}</td>
                                <td class="px-4 py-3 text-center font-bold">{{ $item->quantity }}</td>
                                <td class="px-4 py-3 text-right"><x-currency :amount="$item->unit_price" /></td>
                                <td class="px-4 py-3 text-right font-bold text-ir-bone"><x-currency :amount="$item->total_price" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pt-4 border-t border-ir-copper flex justify-end">
                <div class="w-64 space-y-2 text-xs">
                    <div class="flex justify-between text-ir-bone/70">
                        <span>Subtotal:</span>
                        <span class="font-bold text-ir-bone"><x-currency :amount="$invoice->subtotal" /></span>
                    </div>
                    @if($invoice->discount_amount > 0)
                        <div class="flex justify-between text-ir-bone/70">
                            <span>Discount Applied:</span>
                            <span class="font-bold text-emerald-400">-<x-currency :amount="$invoice->discount_amount" /></span>
                        </div>
                    @endif
                    <div class="flex justify-between text-base font-bold text-ir-bone border-t border-ir-copper pt-2">
                        <span>Total Due:</span>
                        <span class="text-ir-gold"><x-currency :amount="$invoice->total_amount" /></span>
                    </div>
                    <div class="flex justify-between text-xs font-semibold text-emerald-400">
                        <span>Paid Amount:</span>
                        <span><x-currency :amount="$invoice->paid_amount" /></span>
                    </div>
                    <div class="flex justify-between text-xs font-bold text-red-400">
                        <span>Remaining Balance:</span>
                        <span><x-currency :amount="max(0, $invoice->total_amount - $invoice->paid_amount)" /></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment History Log -->
        <div class="bg-ir-carbon border border-ir-copper rounded-md p-6 space-y-4">
            <h4 class="text-xs font-bold text-ir-bone/70 uppercase tracking-wider border-b border-ir-copper pb-3">
                <i class="fa-solid fa-receipt text-ir-gold mr-1"></i> Recorded Transactions ({{ $invoice->payments->count() }})
            </h4>

            <div class="space-y-3">
                @forelse($invoice->payments as $pay)
                    <div class="p-3 rounded-md bg-ir-void border border-ir-copper space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <strong class="text-emerald-400 font-mono"><x-currency :amount="$pay->amount" /></strong>
                            <span class="text-ir-bone/70">{{ $pay->payment_method }}</span>
                        </div>
                        <p class="text-[11px] text-ir-bone/70">Ref: {{ $pay->reference_number ?? 'N/A' }} | Date: {{ $pay->payment_date->format('M d, Y') }}</p>
                        <span class="text-[10px] text-ir-copper block">Collector: {{ $pay->user?->name ?? 'Staff' }}</span>
                    </div>
                @empty
                    <div class="text-center py-6 text-ir-copper text-xs">No payments recorded yet.</div>
                @endforelse
            </div>
        </div>

    </div>

    <!-- Record Payment Modal -->
    <div x-show="openPayModal" class="fixed inset-0 z-50 bg-ir-void/80 backdrop-blur-sm flex items-center justify-center p-4" x-cloak>
        <div class="bg-ir-carbon border border-ir-copper rounded-md p-6 max-w-md w-full space-y-4">
            <div class="flex items-center justify-between border-b border-ir-copper pb-3">
                <h3 class="text-base font-bold text-ir-bone">Record Customer Payment</h3>
                <button @click="openPayModal = false" class="text-ir-bone/70 hover:text-ir-bone"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form action="{{ route('invoices.record_payment', $invoice) }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-ir-bone/70 uppercase mb-1">Payment Amount (₱)</label>
                    <input type="number" step="0.01" name="amount" value="{{ max(0, $invoice->total_amount - $invoice->paid_amount) }}" required class="w-full px-4 py-2.5 rounded-md bg-ir-void border border-ir-copper text-ir-bone font-bold text-lg text-emerald-400">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-ir-bone/70 uppercase mb-1">Payment Method</label>
                    <select name="payment_method" required class="w-full px-4 py-2.5 rounded-md bg-ir-void border border-ir-copper text-sm text-ir-bone">
                        <option value="Cash">Cash</option>
                        <option value="Credit Card">Credit / Debit Card</option>
                        <option value="GCash">GCash / Mobile Wallet</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-ir-bone/70 uppercase mb-1">Reference Number / Txn ID</label>
                    <input type="text" name="reference_number" placeholder="Optional transaction ID..." class="w-full px-4 py-2.5 rounded-md bg-ir-void border border-ir-copper text-sm text-ir-bone">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-ir-bone/70 uppercase mb-1">Collector Notes</label>
                    <textarea name="notes" rows="2" class="w-full px-4 py-2.5 rounded-md bg-ir-void border border-ir-copper text-sm text-ir-bone"></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="openPayModal = false" class="px-4 py-2 rounded-md bg-ir-carbon text-ir-bone text-xs">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-md bg-emerald-600 hover:bg-emerald-500 text-ir-bone text-xs font-bold transition-colors">
                        Submit Payment & Update Invoice
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
