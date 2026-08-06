@extends('layouts.app')

@section('title', 'Supplier: ' . $supplier->name)

@section('content')
<div class="space-y-6">

    <div class="bg-ir-carbon border border-ir-copper rounded-md p-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-ir-bone">{{ $supplier->name }}</h2>
            <p class="text-xs text-ir-bone/70 mt-1">Contact: <strong>{{ $supplier->contact_person ?? 'N/A' }}</strong> | Phone: {{ $supplier->phone ?? 'N/A' }} | Email: {{ $supplier->email ?? 'N/A' }}</p>
        </div>

        <div>
            <a href="{{ route('suppliers.create_po', $supplier) }}" class="px-5 py-2.5 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone font-semibold text-xs transition-colors">
                + Create Purchase Order
            </a>
        </div>
    </div>

    <!-- Restock History Table -->
    <div class="bg-ir-carbon border border-ir-copper rounded-md p-6 space-y-4">
        <h4 class="text-xs font-bold text-ir-bone/70 uppercase tracking-wider border-b border-ir-copper pb-3">
            Purchase Orders & Restock History ({{ $supplier->purchaseOrders->count() }})
        </h4>

        <div class="space-y-4">
            @forelse($supplier->purchaseOrders as $po)
                <div class="p-4 rounded-md bg-ir-void border border-ir-copper space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <strong class="text-ir-gold font-mono text-sm">#{{ $po->po_number }}</strong>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                {{ strtoupper($po->status) }}
                            </span>
                        </div>
                        <span class="text-sm font-bold text-ir-bone">₱{{ number_format($po->total_amount, 2) }}</span>
                    </div>

                    <!-- Items Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-ir-bone">
                            <thead class="bg-ir-carbon text-ir-bone/70 uppercase border-b border-ir-copper">
                                <tr>
                                    <th class="px-3 py-2">Part Item</th>
                                    <th class="px-3 py-2 text-center">Qty Restocked</th>
                                    <th class="px-3 py-2 text-right">Unit Cost</th>
                                    <th class="px-3 py-2 text-right">Total Line Cost</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-ir-copper/60">
                                @foreach($po->items as $item)
                                    <tr>
                                        <td class="px-3 py-2 font-medium text-ir-bone">{{ $item->part?->name }} (SKU: {{ $item->part?->sku }})</td>
                                        <td class="px-3 py-2 text-center font-bold text-emerald-400">+{{ $item->quantity }}</td>
                                        <td class="px-3 py-2 text-right">₱{{ number_format($item->unit_cost, 2) }}</td>
                                        <td class="px-3 py-2 text-right font-bold">₱{{ number_format($item->total_cost, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="text-center py-6 text-ir-copper text-xs">No purchase orders created for this supplier yet.</div>
            @endforelse
        </div>
    </div>

</div>
@endsection
