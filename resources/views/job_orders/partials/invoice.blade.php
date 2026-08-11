{{-- Invoice Tab Partial --}}
{{-- Variables inherited: $jobOrder, $availableParts --}}

<div class="space-y-5" x-data="{ openPayModal: false }">

    {{-- Invoice Header --}}
    @if($jobOrder->invoice)
        @php $invoice = $jobOrder->invoice; @endphp
        <div class="bg-ir-void border border-ir-copper rounded-md p-5 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-3">
                    <h4 class="text-base font-bold text-ir-bone">{{ $invoice->invoice_number }}</h4>
                    <x-status-badge :stage="$invoice->payment_status" />
                </div>
                <p class="text-xs text-ir-bone/70 mt-1">Issued {{ $invoice->issue_date?->format('M d, Y') }} · Due {{ $invoice->due_date?->format('M d, Y') ?? 'N/A' }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('invoices.receipt', $invoice) }}" target="_blank" class="btn-secondary btn-sm">
                    <i class="fa-solid fa-print"></i> Print
                </a>
                <a href="{{ route('invoices.pdf', $invoice) }}" class="btn-secondary btn-sm">
                    <i class="fa-solid fa-file-pdf text-red-400"></i> PDF
                </a>
                @if($invoice->payment_status !== 'paid')
                <button @click="openPayModal = true" class="btn-primary btn-sm">
                    <i class="fa-solid fa-cash-register"></i> Record Payment
                </button>
                @endif
            </div>
        </div>

        {{-- Invoice Line Items --}}
        <div class="bg-ir-void border border-ir-copper rounded-md p-5 space-y-4">
            <h4 class="text-xs font-bold text-ir-bone/70 uppercase tracking-wider">
                <i class="fa-solid fa-list text-ir-gold mr-1"></i> Itemized Charges
            </h4>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-ir-bone">
                    <thead class="bg-ir-carbon border-b border-ir-copper text-ir-bone/70 uppercase text-[10px]">
                        <tr>
                            <th class="px-3 py-2">Type</th>
                            <th class="px-3 py-2">Description</th>
                            <th class="px-3 py-2 text-center">Qty</th>
                            <th class="px-3 py-2 text-right">Unit Price</th>
                            <th class="px-3 py-2 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ir-copper/40">
                        @foreach($invoice->items as $item)
                        <tr class="hover:bg-ir-carbon/40">
                            <td class="px-3 py-2 capitalize text-ir-gold font-semibold">{{ str_replace('_', ' ', $item->item_type) }}</td>
                            <td class="px-3 py-2">{{ $item->description }}</td>
                            <td class="px-3 py-2 text-center font-bold">{{ $item->quantity }}</td>
                            <td class="px-3 py-2 text-right font-mono"><x-currency :amount="$item->unit_price" /></td>
                            <td class="px-3 py-2 text-right font-bold font-mono"><x-currency :amount="$item->total_price" /></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end pt-2">
                <div class="w-56 space-y-1 text-xs">
                    <div class="flex justify-between text-ir-bone/70">
                        <span>Subtotal:</span>
                        <span class="font-bold text-ir-bone font-mono"><x-currency :amount="$invoice->subtotal" /></span>
                    </div>
                    @if($invoice->discount_amount > 0)
                    <div class="flex justify-between text-ir-bone/70">
                        <span>Discount:</span>
                        <span class="font-bold text-ir-signal-green font-mono">−<x-currency :amount="$invoice->discount_amount" /></span>
                    </div>
                    @endif
                    <div class="flex justify-between text-sm font-bold text-ir-bone border-t border-ir-copper pt-2">
                        <span>Total Due:</span>
                        <span class="text-ir-gold font-mono"><x-currency :amount="$invoice->total_amount" /></span>
                    </div>
                    <div class="flex justify-between text-xs font-semibold text-ir-signal-green">
                        <span>Paid:</span>
                        <span class="font-mono"><x-currency :amount="$invoice->paid_amount" /></span>
                    </div>
                    <div class="flex justify-between text-xs font-bold text-ir-alert">
                        <span>Balance:</span>
                        <span class="font-mono"><x-currency :amount="max(0, $invoice->total_amount - $invoice->paid_amount)" /></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Payment History --}}
        @if($invoice->payments->count())
        <div class="bg-ir-void border border-ir-copper rounded-md p-5 space-y-3">
            <h4 class="text-xs font-bold text-ir-bone/70 uppercase tracking-wider">
                <i class="fa-solid fa-receipt text-ir-gold mr-1"></i> Payment History ({{ $invoice->payments->count() }})
            </h4>
            <div class="space-y-2">
                @foreach($invoice->payments as $pay)
                <div class="p-3 rounded-md bg-ir-carbon border border-ir-copper flex items-center justify-between text-xs">
                    <div>
                        <strong class="text-ir-signal-green font-mono"><x-currency :amount="$pay->amount" /></strong>
                        <span class="ml-2 text-ir-bone/70">{{ $pay->payment_method }}</span>
                        <span class="ml-2 text-ir-copper">Ref: {{ $pay->reference_number ?? 'N/A' }}</span>
                    </div>
                    <span class="text-ir-bone/50">{{ $pay->payment_date->format('M d, Y') }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Record Payment Modal --}}
        <div x-show="openPayModal" class="fixed inset-0 z-50 bg-ir-void/80 backdrop-blur-sm flex items-center justify-center p-4" x-cloak>
            <div class="bg-ir-carbon border border-ir-copper rounded-md p-6 max-w-md w-full space-y-4">
                <div class="flex items-center justify-between border-b border-ir-copper pb-3">
                    <h3 class="text-base font-bold text-ir-bone">Record Payment</h3>
                    <button @click="openPayModal = false" class="text-ir-bone/70 hover:text-ir-bone"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <form action="{{ route('invoices.record_payment', $invoice) }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="ir-label">Payment Amount (₱)</label>
                        <input type="number" step="0.01" name="amount"
                               value="{{ max(0, $invoice->total_amount - $invoice->paid_amount) }}"
                               required class="ir-input font-bold text-lg text-right text-ir-signal-green">
                    </div>
                    <div>
                        <label class="ir-label">Payment Method</label>
                        <select name="payment_method" required class="ir-select">
                            <option>Cash</option>
                            <option>GCash / Mobile Wallet</option>
                            <option>Credit Card</option>
                            <option>Bank Transfer</option>
                        </select>
                    </div>
                    <div>
                        <label class="ir-label">Reference Number</label>
                        <input type="text" name="reference_number" placeholder="Optional transaction ID..." class="ir-input text-sm">
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="openPayModal = false" class="btn-secondary btn-sm">Cancel</button>
                        <button type="submit" class="btn-primary">Submit Payment</button>
                    </div>
                </form>
            </div>
        </div>

    @else
        {{-- Generate Invoice CTA --}}
        <div class="bg-ir-void border border-ir-copper/60 rounded-md p-10 text-center">
            <div class="max-w-md mx-auto space-y-3">
                <div class="w-12 h-12 rounded-full bg-ir-carbon border border-ir-copper text-ir-gold flex items-center justify-center mx-auto text-xl shadow-inner">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                </div>
                <h4 class="text-sm font-bold text-ir-bone">No official invoice generated yet</h4>
                <p class="text-xs text-ir-bone/60 leading-relaxed">
                    Generate an official billing invoice summarizing labor, parts, and service fees for customer payment processing.
                </p>
                <div class="pt-2">
                    <form action="{{ route('invoices.generate', $jobOrder) }}" method="POST" class="inline-block">
                        @csrf
                        <button type="submit" class="btn-primary">
                            <i class="fa-solid fa-file-circle-plus"></i> Generate Billing Invoice
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Cost Calculation Form (always visible) --}}
    <div class="bg-ir-void border border-ir-copper rounded-md p-5 space-y-4">
        <h4 class="text-xs font-bold text-ir-bone/70 uppercase tracking-wider border-b border-ir-copper pb-3">
            <i class="fa-solid fa-calculator text-ir-gold mr-1"></i> Cost Calculator
            <span class="ml-2 text-ir-gold font-mono text-sm"><x-currency :amount="$jobOrder->total_cost" /></span>
        </h4>
        <form action="{{ route('job_orders.update_costs', $jobOrder) }}" method="POST" class="space-y-3 text-xs">
            @csrf
            @method('PATCH')
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="ir-label">Labor Cost (₱)</label>
                    <input type="number" step="0.01" name="labor_cost" value="{{ $jobOrder->labor_cost }}" class="ir-input font-bold text-right">
                </div>
                <div>
                    <label class="ir-label">Parts Cost (Auto)</label>
                    <input type="text" readonly value="₱{{ number_format($jobOrder->parts_cost, 2) }}" class="ir-input opacity-60 text-right">
                </div>
                <div>
                    <label class="ir-label">Service Fee (₱)</label>
                    <input type="number" step="0.01" name="service_fee" value="{{ $jobOrder->service_fee }}" class="ir-input font-bold text-right">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="ir-label">Discount Type</label>
                    <select name="discount_type" class="ir-select">
                        <option value="fixed" {{ $jobOrder->discount_type === 'fixed' ? 'selected' : '' }}>Fixed (₱)</option>
                        <option value="percentage" {{ $jobOrder->discount_type === 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                    </select>
                </div>
                <div>
                    <label class="ir-label">Discount Value</label>
                    <input type="number" step="0.01" name="discount_value" value="{{ $jobOrder->discount_value }}" class="ir-input font-bold text-right">
                </div>
            </div>
            <button type="submit" class="btn-primary w-full">
                <i class="fa-solid fa-calculator"></i> Recalculate Total Cost
            </button>
        </form>
    </div>

    {{-- Parts Management --}}
    <div class="bg-ir-void border border-ir-copper rounded-md p-5 space-y-4">
        <h4 class="text-xs font-bold text-ir-bone/70 uppercase tracking-wider border-b border-ir-copper pb-3">
            <i class="fa-solid fa-boxes-stacked text-ir-gold mr-1"></i> Replacement Parts Used
        </h4>

        <form action="{{ route('job_orders.add_part', $jobOrder) }}" method="POST" class="flex flex-col sm:flex-row gap-2">
            @csrf
            <select name="part_id" required class="ir-select flex-1 text-sm">
                <option value="">-- Choose Replacement Part --</option>
                @foreach($availableParts as $part)
                    <option value="{{ $part->id }}">
                        {{ $part->name }} (SKU: {{ $part->sku }}) · Stock: {{ $part->stock_quantity }} · <x-currency :amount="$part->selling_price" />
                    </option>
                @endforeach
            </select>
            <input type="number" name="quantity" value="1" min="1" required class="ir-input w-20 text-center">
            <button type="submit" class="btn-primary btn-sm">+ Add Part</button>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-ir-bone">
                <thead class="bg-ir-carbon font-semibold text-ir-bone/70 uppercase border-b border-ir-copper">
                    <tr>
                        <th class="px-3 py-2">Part</th>
                        <th class="px-3 py-2 text-center">Qty</th>
                        <th class="px-3 py-2 text-right">Unit Price</th>
                        <th class="px-3 py-2 text-right">Subtotal</th>
                        <th class="px-3 py-2 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ir-copper/40">
                    @forelse($jobOrder->parts as $jPart)
                    <tr class="hover:bg-ir-carbon/40">
                        <td class="px-3 py-2 font-medium">
                            {{ $jPart->part?->name }}
                            <span class="block text-[10px] text-ir-copper">SKU: {{ $jPart->part?->sku }}</span>
                        </td>
                        <td class="px-3 py-2 text-center font-bold">{{ $jPart->quantity }}</td>
                        <td class="px-3 py-2 text-right font-mono"><x-currency :amount="$jPart->unit_price" /></td>
                        <td class="px-3 py-2 text-right font-bold font-mono"><x-currency :amount="$jPart->total_price" /></td>
                        <td class="px-3 py-2 text-center">
                            <form action="{{ route('job_orders.remove_part', [$jobOrder, $jPart]) }}" method="POST">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-ir-alert hover:text-red-300" title="Remove part and restore stock">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-3 py-5 text-center text-ir-copper">No parts attached to this job.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
