@extends('layouts.app')

@section('title', 'Parts Inventory & Stock')

@section('content')
<div class="space-y-6">

    <!-- Top Valuation Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="bg-ir-carbon border border-ir-copper rounded-md p-5">
            <span class="text-xs font-semibold text-ir-bone/70 uppercase tracking-wider">Inventory Cost Valuation</span>
            <h3 class="text-2xl font-extrabold text-ir-bone mt-1">₱{{ number_format($totalValuationCost, 2) }}</h3>
            <span class="text-xs text-ir-bone/70 mt-1 inline-block">Total purchasing value on hand</span>
        </div>

        <div class="bg-ir-carbon border border-ir-copper rounded-md p-5">
            <span class="text-xs font-semibold text-ir-bone/70 uppercase tracking-wider">Retail Sales Potential</span>
            <h3 class="text-2xl font-extrabold text-emerald-400 mt-1">₱{{ number_format($totalValuationRetail, 2) }}</h3>
            <span class="text-xs text-emerald-400/80 mt-1 inline-block">Potential revenue value</span>
        </div>

        <div class="bg-ir-carbon border border-ir-copper rounded-md p-5">
            <span class="text-xs font-semibold text-ir-bone/70 uppercase tracking-wider">Low Stock Threshold Items</span>
            <h3 class="text-2xl font-extrabold text-amber-400 mt-1">{{ $lowStockCount }} Parts</h3>
            <span class="text-xs text-amber-400/80 mt-1 inline-block">At or below reorder level</span>
        </div>
    </div>

    <!-- Filters & Actions -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-ir-carbon border border-ir-copper p-5 rounded-md">
        <form action="{{ route('inventory.index') }}" method="GET" class="w-full sm:w-auto flex-1 grid grid-cols-1 sm:grid-cols-4 gap-3">
            <input type="text" name="search" value="{{ $search }}" placeholder="Search part name, SKU, barcode, models..." class="px-4 py-2 rounded-md bg-ir-void border border-ir-copper text-sm text-ir-bone focus:outline-none">
            
            <select name="category_id" class="px-4 py-2 rounded-md bg-ir-void border border-ir-copper text-sm text-ir-bone focus:outline-none">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>

            <select name="supplier_id" class="px-4 py-2 rounded-md bg-ir-void border border-ir-copper text-sm text-ir-bone focus:outline-none">
                <option value="">All Suppliers</option>
                @foreach($suppliers as $sup)
                    <option value="{{ $sup->id }}" {{ $supplierId == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
                @endforeach
            </select>

            <button type="submit" class="py-2 px-4 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone font-medium text-sm transition-colors">
                <i class="fa-solid fa-filter mr-1"></i> Filter
            </button>
        </form>

        @can('parts.catalog.manage')
        <a href="{{ route('inventory.create') }}" class="w-full sm:w-auto px-5 py-2.5 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone font-semibold text-sm flex items-center justify-center gap-2 transition-colors">
            <i class="fa-solid fa-plus"></i> Add New Part
        </a>
        @endcan
    </div>

    <!-- Inventory Table -->
    <div class="bg-ir-carbon border border-ir-copper rounded-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-ir-bone">
                <thead class="bg-ir-void text-xs font-semibold text-ir-bone/70 uppercase tracking-wider border-b border-ir-copper">
                    <tr>
                        <th class="px-6 py-4">SKU & Part Name</th>
                        <th class="px-6 py-4">Category</th>
                        <th class="px-6 py-4 text-center">Rack / Location</th>
                        <th class="px-6 py-4 text-right">Cost Price</th>
                        <th class="px-6 py-4 text-right">Selling Price</th>
                        <th class="px-6 py-4 text-center">Stock Level</th>
                        <th class="px-6 py-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ir-copper">
                    @forelse($parts as $p)
                        @php
                            $isLow = $p->isLowStock();
                        @endphp
                        <tr class="hover:bg-ir-carbon/50 transition-colors {{ $isLow ? 'bg-amber-950/20' : '' }}">
                            <td class="px-6 py-4 font-medium text-ir-bone">
                                <a href="{{ route('inventory.show', $p) }}" class="font-bold text-ir-gold hover:underline">
                                    {{ $p->name }}
                                </a>
                                <div class="flex items-center gap-2 text-xs text-ir-bone/70 mt-0.5">
                                    <span>SKU: <strong class="font-mono text-ir-bone">{{ $p->sku }}</strong></span> | 
                                    <span>Barcode: <strong class="font-mono text-ir-bone/70">{{ $p->barcode }}</strong></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-xs text-ir-bone">
                                {{ $p->category?->name ?? 'General' }}
                            </td>
                            <td class="px-6 py-4 text-center font-mono text-xs text-ir-bone/70">
                                {{ $p->location_rack ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-right font-mono text-ir-bone/70">
                                ₱{{ number_format($p->cost_price, 2) }}
                            </td>
                            <td class="px-6 py-4 text-right font-mono font-bold text-ir-bone">
                                ₱{{ number_format($p->selling_price, 2) }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1 rounded-full text-xs font-bold border 
                                    {{ $isLow ? 'bg-red-500/10 text-red-400 border-red-500/30' : 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' }}">
                                    {{ $p->stock_quantity }} {{ $isLow ? '(Low Stock!)' : 'units' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('inventory.barcode', $p) }}" target="_blank"
                                       class="p-2 rounded-lg bg-ir-void border border-ir-copper/50 text-ir-bone/70 hover:bg-ir-gold/10 hover:text-ir-gold hover:border-ir-gold/50 transition-colors"
                                       title="Print Barcode Label">
                                        <i class="fa-solid fa-barcode"></i>
                                    </a>
                                    <a href="{{ route('inventory.show', $p) }}"
                                       class="p-2 rounded-lg bg-ir-void border border-ir-copper/50 text-ir-bone/70 hover:bg-blue-500/10 hover:text-blue-400 hover:border-blue-500/40 transition-colors"
                                       title="View Stock Details">
                                        <i class="fa-solid fa-boxes-packing"></i>
                                    </a>
                                    @can('parts.catalog.manage')
                                    <a href="{{ route('inventory.edit', $p) }}"
                                       class="p-2 rounded-lg bg-ir-void border border-ir-copper/50 text-ir-bone/70 hover:bg-amber-500/10 hover:text-amber-400 hover:border-amber-500/40 transition-colors"
                                       title="Edit Part Details">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <form action="{{ route('inventory.destroy', $p) }}" method="POST"
                                          onsubmit="return confirm('Delete part \'{{ addslashes($p->name) }}\'? This cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="p-2 rounded-lg bg-ir-void border border-ir-copper/50 text-ir-bone/70 hover:bg-red-500/10 hover:text-red-400 hover:border-red-500/40 transition-colors"
                                                title="Delete Part">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-ir-copper">No inventory parts found matching criteria.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($parts->hasPages())
            <div class="px-6 py-4 border-t border-ir-copper bg-ir-void">
                {{ $parts->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
