@extends('layouts.app')

@section('title', 'Edit Technician: ' . $technician->name)

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-ir-bone">Edit Technician Profile</h2>
        <a href="{{ route('technicians.show', $technician) }}" class="px-4 py-2 rounded-md bg-ir-carbon border border-ir-copper text-ir-bone text-sm hover:bg-ir-copper/20 transition-colors">Cancel</a>
    </div>

    <form action="{{ route('technicians.update', $technician) }}" method="POST" class="bg-ir-carbon border border-ir-copper rounded-md p-6 space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label for="name" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Technician Full Name *</label>
            <input type="text" id="name" name="name" value="{{ old('name', $technician->name) }}" required
                   class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm focus:outline-none focus:border-ir-gold">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="phone" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Phone Number</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone', $technician->phone) }}"
                       class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm focus:outline-none focus:border-ir-gold">
            </div>

            <div>
                <label for="specialty" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Technical Specialty *</label>
                <input type="text" id="specialty" name="specialty" value="{{ old('specialty', $technician->specialty) }}"
                       placeholder="e.g. iPhone &amp; Micro-soldering" required
                       class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm focus:outline-none focus:border-ir-gold">
            </div>
        </div>

        <div>
            <label class="flex items-center gap-2 cursor-pointer text-ir-bone text-sm">
                <input type="checkbox" name="is_active" value="1" {{ $technician->is_active ? 'checked' : '' }}
                       class="rounded bg-ir-void border-ir-copper text-ir-gold">
                <span>Technician Account Active</span>
            </label>
            <p class="text-[11px] text-ir-copper mt-1">Inactive technicians cannot be assigned new job orders.</p>
        </div>

        <div class="pt-4 border-t border-ir-copper flex justify-end gap-3">
            <a href="{{ route('technicians.show', $technician) }}" class="btn-secondary btn-sm">Cancel</a>
            <button type="submit" class="px-6 py-3 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone font-semibold text-sm transition-colors">
                <i class="fa-solid fa-floppy-disk mr-1"></i> Save Changes
            </button>
        </div>
    </form>
</div>
@endsection
