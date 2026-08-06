@extends('layouts.app')

@section('title', 'Register Device')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-ir-bone">Register Device</h2>
        <a href="{{ route('devices.index') }}" class="px-4 py-2 rounded-md bg-ir-carbon text-ir-bone text-sm">Cancel</a>
    </div>

    <form action="{{ route('devices.store') }}" method="POST" class="bg-ir-carbon border border-ir-copper rounded-md p-6 space-y-4">
        @csrf

        <div>
            <label for="customer_id" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Customer Owner *</label>
            <select id="customer_id" name="customer_id" required class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm focus:border-ir-gold focus:outline-none">
                @foreach($customers as $c)
                    <option value="{{ $c->id }}" {{ $customerId == $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->phone }})</option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label for="device_type" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Category *</label>
                <select id="device_type" name="device_type" required class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm">
                    <option value="Mobile">Mobile Phone</option>
                    <option value="Laptop">Laptop / Notebook</option>
                    <option value="Tablet">Tablet / iPad</option>
                    <option value="Smartwatch">Smartwatch</option>
                    <option value="Console">Gaming Console</option>
                    <option value="Other">Other Gadget</option>
                </select>
            </div>

            <div>
                <label for="brand" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Brand *</label>
                <input type="text" id="brand" name="brand" placeholder="Apple, Samsung, Dell..." required class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm">
            </div>

            <div>
                <label for="model" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Model Name *</label>
                <input type="text" id="model" name="model" placeholder="iPhone 14 Pro, Galaxy S23..." required class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label for="serial_number" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Serial Number / IMEI</label>
                <input type="text" id="serial_number" name="serial_number" class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm font-mono">
            </div>

            <div>
                <label for="color" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Color</label>
                <input type="text" id="color" name="color" placeholder="Space Gray, Black..." class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm">
            </div>

            <div>
                <label for="passcode_pattern" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Passcode / PIN / Pattern</label>
                <input type="text" id="passcode_pattern" name="passcode_pattern" placeholder="123456 / PIN" class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm font-mono">
            </div>
        </div>

        <div>
            <label for="notes" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Device Condition Notes</label>
            <textarea id="notes" name="notes" rows="2" class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm"></textarea>
        </div>

        <div class="pt-4 border-t border-ir-copper flex justify-end">
            <button type="submit" class="px-6 py-3 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone font-semibold text-sm transition-colors">
                Save Device Specifications
            </button>
        </div>
    </form>
</div>
@endsection
