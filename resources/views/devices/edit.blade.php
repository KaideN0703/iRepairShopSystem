@extends('layouts.app')

@section('title', 'Edit Device')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-ir-bone">Edit Device Details</h2>
        <a href="{{ route('devices.show', $device) }}" class="px-4 py-2 rounded-md bg-ir-carbon text-ir-bone text-sm">Cancel</a>
    </div>

    <form action="{{ route('devices.update', $device) }}" method="POST" class="bg-ir-carbon border border-ir-copper rounded-md p-6 space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label for="customer_id" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Customer Owner *</label>
            <select id="customer_id" name="customer_id" required class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm">
                @foreach($customers as $c)
                    <option value="{{ $c->id }}" {{ $device->customer_id == $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->phone }})</option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label for="device_type" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Category *</label>
                <select id="device_type" name="device_type" required class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm">
                    <option value="Mobile" {{ $device->device_type === 'Mobile' ? 'selected' : '' }}>Mobile Phone</option>
                    <option value="Laptop" {{ $device->device_type === 'Laptop' ? 'selected' : '' }}>Laptop / Notebook</option>
                    <option value="Tablet" {{ $device->device_type === 'Tablet' ? 'selected' : '' }}>Tablet / iPad</option>
                    <option value="Smartwatch" {{ $device->device_type === 'Smartwatch' ? 'selected' : '' }}>Smartwatch</option>
                    <option value="Console" {{ $device->device_type === 'Console' ? 'selected' : '' }}>Gaming Console</option>
                    <option value="Other" {{ $device->device_type === 'Other' ? 'selected' : '' }}>Other Gadget</option>
                </select>
            </div>

            <div>
                <label for="brand" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Brand *</label>
                <input type="text" id="brand" name="brand" value="{{ old('brand', $device->brand) }}" required class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm">
            </div>

            <div>
                <label for="model" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Model Name *</label>
                <input type="text" id="model" name="model" value="{{ old('model', $device->model) }}" required class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label for="serial_number" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Serial Number / IMEI</label>
                <input type="text" id="serial_number" name="serial_number" value="{{ old('serial_number', $device->serial_number) }}" class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm font-mono">
            </div>

            <div>
                <label for="color" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Color</label>
                <input type="text" id="color" name="color" value="{{ old('color', $device->color) }}" class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm">
            </div>

            <div>
                <label for="passcode_pattern" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Passcode / PIN / Pattern</label>
                <input type="text" id="passcode_pattern" name="passcode_pattern" value="{{ old('passcode_pattern', $device->passcode_pattern) }}" class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm font-mono">
            </div>
        </div>

        <div>
            <label for="notes" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Device Condition Notes</label>
            <textarea id="notes" name="notes" rows="2" class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm">{{ old('notes', $device->notes) }}</textarea>
        </div>

        <div class="pt-4 border-t border-ir-copper flex justify-end">
            <button type="submit" class="px-6 py-3 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone font-semibold text-sm">
                Update Device Details
            </button>
        </div>
    </form>
</div>
@endsection
