@extends('layouts.app')

@section('title', 'Edit Customer')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-ir-bone">Edit Customer Profile</h2>
        <a href="{{ route('customers.show', $customer) }}" class="px-4 py-2 rounded-md bg-ir-carbon text-ir-bone text-sm">Cancel</a>
    </div>

    <form action="{{ route('customers.update', $customer) }}" method="POST" class="bg-ir-carbon border border-ir-copper rounded-md p-6 space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label for="name" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Customer Full Name *</label>
            <input type="text" id="name" name="name" value="{{ old('name', $customer->name) }}" required class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm focus:border-ir-gold focus:outline-none">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="phone" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Phone Number *</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone', $customer->phone) }}" required class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm focus:border-ir-gold focus:outline-none">
            </div>

            <div>
                <label for="email" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Email Address</label>
                <input type="email" id="email" name="email" value="{{ old('email', $customer->email) }}" class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm focus:border-ir-gold focus:outline-none">
            </div>
        </div>

        <div>
            <label for="address" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Physical Address</label>
            <textarea id="address" name="address" rows="2" class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm focus:border-ir-gold focus:outline-none">{{ old('address', $customer->address) }}</textarea>
        </div>

        <div>
            <label for="notes" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Customer Notes / Preferences</label>
            <textarea id="notes" name="notes" rows="2" class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm focus:border-ir-gold focus:outline-none">{{ old('notes', $customer->notes) }}</textarea>
        </div>

        <div class="pt-4 border-t border-ir-copper flex justify-end">
            <button type="submit" class="px-6 py-3 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone font-semibold text-sm transition-colors">
                Update Customer Profile
            </button>
        </div>
    </form>
</div>
@endsection
