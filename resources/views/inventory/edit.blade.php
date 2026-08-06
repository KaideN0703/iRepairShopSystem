@extends('layouts.app')

@section('title', 'Edit Inventory Part')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-ir-bone">Edit Part Details</h2>
        <a href="{{ route('inventory.show', $part) }}" class="px-4 py-2 rounded-md bg-ir-carbon text-ir-bone text-sm">Cancel</a>
    </div>

    <form action="{{ route('inventory.update', $part) }}" method="POST" class="bg-ir-carbon border border-ir-copper rounded-md p-6 shadow-lg space-y-4">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="sku" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">SKU Code *</label>
                <input type="text" id="sku" name="sku" value="{{ old('sku', $part->sku) }}" required class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm font-mono">
            </div>

            <div>
                <label for="barcode" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Barcode String *</label>
                <input type="text" id="barcode" name="barcode" value="{{ old('barcode', $part->barcode) }}" required class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm font-mono">
            </div>
        </div>

        <div>
            <label for="name" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Part Name / Title *</label>
            <input type="text" id="name" name="name" value="{{ old('name', $part->name) }}" required class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="category_id" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Category *</label>
                <select id="category_id" name="category_id" required class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm">
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ $part->category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="supplier_id" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Primary Supplier</label>
                <select id="supplier_id" name="supplier_id" class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm">
                    <option value="">Unassigned</option>
                    @foreach($suppliers as $sup)
                        <option value="{{ $sup->id }}" {{ $part->supplier_id == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label for="cost_price" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Cost Price (₱) *</label>
                <input type="number" step="0.01" id="cost_price" name="cost_price" value="{{ old('cost_price', $part->cost_price) }}" required class="w-full px-3 py-2.5 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm">
            </div>

            <div>
                <label for="selling_price" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Selling Price (₱) *</label>
                <input type="number" step="0.01" id="selling_price" name="selling_price" value="{{ old('selling_price', $part->selling_price) }}" required class="w-full px-3 py-2.5 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm font-bold text-ir-gold">
            </div>

            <div>
                <label for="reorder_level" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Reorder Level *</label>
                <input type="number" id="reorder_level" name="reorder_level" value="{{ old('reorder_level', $part->reorder_level) }}" required class="w-full px-3 py-2.5 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="location_rack" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Rack Location</label>
                <input type="text" id="location_rack" name="location_rack" value="{{ old('location_rack', $part->location_rack) }}" class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm">
            </div>

            <div>
                <label for="compatible_models" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Compatible Device Models</label>
                <input type="text" id="compatible_models" name="compatible_models" value="{{ old('compatible_models', $part->compatible_models) }}" class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm">
            </div>
        </div>

        <div>
            <label for="description" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Technical Description</label>
            <textarea id="description" name="description" rows="2" class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm">{{ old('description', $part->description) }}</textarea>
        </div>

        <div class="pt-4 border-t border-ir-copper flex justify-end">
            <button type="submit" class="px-6 py-3 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone font-semibold text-sm transition-colors">
                Update Part Information
            </button>
        </div>
    </form>
</div>
@endsection
