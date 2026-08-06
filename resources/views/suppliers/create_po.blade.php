@extends('layouts.app')

@section('title', 'Restock PO - ' . $supplier->name)

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-ir-bone">Create Purchase Order & Restock</h2>
        <a href="{{ route('suppliers.show', $supplier) }}" class="px-4 py-2 rounded-md bg-ir-carbon text-ir-bone text-sm">Cancel</a>
    </div>

    <form action="{{ route('suppliers.store_po', $supplier) }}" method="POST" class="bg-ir-carbon border border-ir-copper rounded-md p-6 space-y-4">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Supplier</label>
                <input type="text" readonly value="{{ $supplier->name }}" class="w-full px-4 py-3 rounded-md bg-ir-void/60 border border-ir-copper text-ir-bone/70 font-bold text-sm">
            </div>

            <div>
                <label for="expected_date" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Expected Delivery Date</label>
                <input type="date" id="expected_date" name="expected_date" value="{{ date('Y-m-d', strtotime('+3 days')) }}" class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm">
            </div>
        </div>

        <!-- Part Item Line -->
        <div class="p-4 rounded-md bg-ir-void border border-ir-copper space-y-3">
            <span class="text-xs font-bold text-ir-bone/70 uppercase">Restock Item Line #1</span>
            
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs text-ir-bone/70 mb-1">Select Part</label>
                    <select name="items[0][part_id]" required class="w-full px-3 py-2 rounded-lg bg-ir-carbon border border-ir-copper text-xs text-ir-bone">
                        @foreach($parts as $p)
                            <option value="{{ $p->id }}">{{ $p->name }} (Stock: {{ $p->stock_quantity }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs text-ir-bone/70 mb-1">Quantity to Restock</label>
                    <input type="number" name="items[0][quantity]" value="10" min="1" required class="w-full px-3 py-2 rounded-lg bg-ir-carbon border border-ir-copper text-xs text-ir-bone text-center font-bold">
                </div>

                <div>
                    <label class="block text-xs text-ir-bone/70 mb-1">Unit Cost (₱)</label>
                    <input type="number" step="0.01" name="items[0][unit_cost]" value="25.00" required class="w-full px-3 py-2 rounded-lg bg-ir-carbon border border-ir-copper text-xs text-ir-bone font-bold">
                </div>
            </div>
        </div>

        <div>
            <label for="notes" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Purchase Order Notes</label>
            <textarea id="notes" name="notes" rows="2" placeholder="Notes for vendor or receiving dock..." class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm"></textarea>
        </div>

        <div class="pt-4 border-t border-ir-copper flex justify-end">
            <button type="submit" class="px-6 py-3 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone font-semibold text-sm">
                Submit Purchase Order & Restock Stock
            </button>
        </div>
    </form>
</div>
@endsection
