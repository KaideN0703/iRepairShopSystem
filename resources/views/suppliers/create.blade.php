@extends('layouts.app')

@section('title', 'Add Supplier')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-ir-bone">Add New Supplier</h2>
        <a href="{{ route('suppliers.index') }}" class="px-4 py-2 rounded-md bg-ir-carbon text-ir-bone text-sm">Cancel</a>
    </div>

    <form action="{{ route('suppliers.store') }}" method="POST" class="bg-ir-carbon border border-ir-copper rounded-md p-6 space-y-4">
        @csrf

        <div>
            <label for="name" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Supplier / Vendor Name *</label>
            <input type="text" id="name" name="name" required class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label for="contact_person" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Contact Person</label>
                <input type="text" id="contact_person" name="contact_person" class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm">
            </div>

            <div>
                <label for="phone" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Phone</label>
                <input type="text" id="phone" name="phone" class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm">
            </div>

            <div>
                <label for="email" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Email</label>
                <input type="email" id="email" name="email" class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm">
            </div>
        </div>

        <div>
            <label for="address" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Physical Address</label>
            <textarea id="address" name="address" rows="2" class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm"></textarea>
        </div>

        <div class="pt-4 border-t border-ir-copper flex justify-end">
            <button type="submit" class="px-6 py-3 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone font-semibold text-sm transition-colors">
                Save Supplier Record
            </button>
        </div>
    </form>
</div>
@endsection
