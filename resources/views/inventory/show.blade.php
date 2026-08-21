@extends('layouts.app')

@section('title', 'Part: ' . $part->name)

@section('content')
<div class="space-y-6">

    <div class="bg-ir-carbon border border-ir-copper rounded-md p-6 shadow-lg flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="text-2xl font-bold text-ir-bone">{{ $part->name }}</h2>
                <span class="px-3 py-1 rounded-full text-xs font-mono font-bold bg-ir-amber-deep/10 text-ir-gold border border-ir-gold/30">
                    SKU: {{ $part->sku }}
                </span>
            </div>
            <p class="text-xs text-ir-bone/70 mt-1">
                Category: {{ $part->category?->name }} | Supplier: {{ $part->supplier?->name ?? 'None' }} | Rack: {{ $part->location_rack ?? 'N/A' }}
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('inventory.barcode', $part) }}" target="_blank" class="px-4 py-2 rounded-md bg-ir-carbon hover:bg-ir-carbon text-ir-bone text-xs font-semibold">
                <i class="fa-solid fa-barcode mr-1"></i> Print Label
            </a>
            @can('parts.catalog.manage')
            <a href="{{ route('inventory.edit', $part) }}" class="px-4 py-2 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone text-xs font-semibold transition-colors">
                <i class="fa-solid fa-pen-to-square mr-1"></i> Edit Details
            </a>
            @endcan
        </div>
    </div>

    <!-- Stock Adjust Form & Metrics -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Stock Adjust Box -->
        <div class="bg-ir-carbon border border-ir-copper rounded-md p-6 space-y-4">
            <h4 class="text-xs font-bold text-ir-bone/70 uppercase tracking-wider border-b border-ir-copper pb-3 flex items-center justify-between">
                <span><i class="fa-solid fa-boxes-packing text-ir-gold mr-1"></i> Stock Status</span>
                <x-stock-badge :part="$part" :show-reorder="true" />
            </h4>

            @can('parts.catalog.manage')
            <form action="{{ route('inventory.adjust_stock', $part) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-ir-bone/70 uppercase mb-2">Movement Type</label>
                    <select name="type" required class="w-full px-4 py-2.5 rounded-md bg-ir-void border border-ir-copper text-sm text-ir-bone">
                        <option value="in">Stock In (+ Increase)</option>
                        <option value="out">Stock Out (- Decrease)</option>
                        <option value="adjustment">Manual Count Adjustment</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-ir-bone/70 uppercase mb-2">Quantity</label>
                    <input type="number" name="quantity" min="1" value="1" required class="w-full px-4 py-2.5 rounded-md bg-ir-void border border-ir-copper text-sm text-ir-bone font-bold text-center">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-ir-bone/70 uppercase mb-2">Notes / Reference</label>
                    <input type="text" name="notes" placeholder="e.g. Shipment received / Restock" class="w-full px-4 py-2.5 rounded-md bg-ir-void border border-ir-copper text-sm text-ir-bone">
                </div>

                <button type="submit" class="w-full py-3 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone font-semibold text-xs transition-colors">
                    Apply Stock Adjustment
                </button>
            </form>
            @else
            <div class="text-xs text-ir-bone/70 space-y-2">
                <p><strong>Cost Price:</strong> ₱{{ number_format($part->cost_price, 2) }}</p>
                <p><strong>Selling Price:</strong> ₱{{ number_format($part->selling_price, 2) }}</p>
                <p><strong>Reorder Level:</strong> {{ $part->reorder_level }} units</p>
                <p class="text-[11px] text-ir-copper">Read-only catalog access. Stock adjustments are reserved for Inventory Staff & Management.</p>
            </div>
            @endcan
        </div>

        <!-- Stock Movements Log (2 cols) -->
        <div class="lg:col-span-2 bg-ir-carbon border border-ir-copper rounded-md p-6 space-y-4">
            <h4 class="text-xs font-bold text-ir-bone/70 uppercase tracking-wider border-b border-ir-copper pb-3">
                <i class="fa-solid fa-list-check text-ir-gold mr-1"></i> Immutable Stock Movement Audit History
            </h4>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-ir-bone">
                    <thead class="bg-ir-void font-semibold text-ir-bone/70 uppercase border-b border-ir-copper">
                        <tr>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3 text-center">Quantity</th>
                            <th class="px-4 py-3">Reference / Notes</th>
                            <th class="px-4 py-3 text-right">User</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ir-copper">
                        @forelse($part->stockMovements as $m)
                            <tr class="hover:bg-ir-carbon/40">
                                <td class="px-4 py-3 text-ir-bone/70">{{ $m->created_at->format('M d, Y h:i A') }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded font-bold uppercase text-[10px]
                                        {{ $m->quantity > 0 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20' }}">
                                        {{ $m->type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center font-bold {{ $m->quantity > 0 ? 'text-emerald-400' : 'text-red-400' }}">
                                    {{ $m->quantity > 0 ? '+' : '' }}{{ $m->quantity }}
                                </td>
                                <td class="px-4 py-3 text-ir-bone">{{ $m->notes ?? $m->reference_type }}</td>
                                <td class="px-4 py-3 text-right text-ir-bone/70">{{ $m->user?->name ?? 'System' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-ir-copper">No stock movement logs recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>
@endsection
