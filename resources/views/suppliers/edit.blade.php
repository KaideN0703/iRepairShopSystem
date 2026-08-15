@extends('layouts.app')

@section('title', 'Edit Supplier: ' . $supplier->name)

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-ir-bone">Edit Supplier Profile</h2>
        <a href="{{ route('suppliers.show', $supplier) }}" class="px-4 py-2 rounded-md bg-ir-carbon border border-ir-copper text-ir-bone text-sm hover:bg-ir-copper/20 transition-colors">Cancel</a>
    </div>

    <form action="{{ route('suppliers.update', $supplier) }}" method="POST" class="bg-ir-carbon border border-ir-copper rounded-md p-6 space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label for="name" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Supplier / Vendor Name *</label>
            <input type="text" id="name" name="name" value="{{ old('name', $supplier->name) }}" required
                   class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm focus:outline-none focus:border-ir-gold">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label for="contact_person" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Contact Person</label>
                <input type="text" id="contact_person" name="contact_person" value="{{ old('contact_person', $supplier->contact_person) }}"
                       class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm focus:outline-none focus:border-ir-gold">
            </div>

            <div>
                <label for="phone" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Phone</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone', $supplier->phone) }}"
                       class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm focus:outline-none focus:border-ir-gold">
            </div>

            <div>
                <label for="email" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email', $supplier->email) }}"
                       class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm focus:outline-none focus:border-ir-gold">
            </div>
        </div>

        <div>
            <label for="address" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Physical Address</label>
            <textarea id="address" name="address" rows="2"
                      class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm focus:outline-none focus:border-ir-gold">{{ old('address', $supplier->address) }}</textarea>
        </div>

        <div class="pt-4 border-t border-ir-copper flex justify-end gap-3">
            <a href="{{ route('suppliers.show', $supplier) }}" class="btn-secondary btn-sm">Cancel</a>
            <button type="submit" class="px-6 py-3 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone font-semibold text-sm transition-colors">
                <i class="fa-solid fa-floppy-disk mr-1"></i> Update Supplier
            </button>
        </div>
    </form>
</div>
@endsection
