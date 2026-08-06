@extends('layouts.app')

@section('title', 'Supplier Management')

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between bg-ir-carbon border border-ir-copper p-5 rounded-md">
        <div>
            <h2 class="text-xl font-bold text-ir-bone">Parts Suppliers & Vendors</h2>
            <p class="text-xs text-ir-bone/70 mt-1">Manage vendor contact info, purchase orders, and stock restocking</p>
        </div>

        <a href="{{ route('suppliers.create') }}" class="px-5 py-2.5 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone font-semibold text-sm flex items-center gap-2 transition-colors">
            <i class="fa-solid fa-plus"></i> Add New Supplier
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @forelse($suppliers as $sup)
            <div class="bg-ir-carbon border border-ir-copper rounded-md p-6 space-y-4 flex flex-col justify-between">
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-ir-bone">{{ $sup->name }}</h3>
                        <span class="px-2.5 py-1 rounded-full bg-ir-carbon border border-ir-copper text-xs font-semibold text-ir-gold">
                            {{ $sup->parts_count }} Parts Supplied
                        </span>
                    </div>

                    <p class="text-xs text-ir-bone"><i class="fa-solid fa-user text-ir-copper w-4"></i> Contact: {{ $sup->contact_person ?? 'N/A' }}</p>
                    <p class="text-xs text-ir-bone"><i class="fa-solid fa-phone text-ir-copper w-4"></i> {{ $sup->phone ?? 'N/A' }}</p>
                    <p class="text-xs text-ir-bone"><i class="fa-solid fa-envelope text-ir-copper w-4"></i> {{ $sup->email ?? 'N/A' }}</p>
                    <p class="text-xs text-ir-bone/70 truncate"><i class="fa-solid fa-location-dot text-ir-copper w-4"></i> {{ $sup->address ?? 'N/A' }}</p>
                </div>

                <div class="pt-4 border-t border-ir-copper flex items-center gap-2">
                    <a href="{{ route('suppliers.show', $sup) }}" class="flex-1 py-2 text-center rounded-md bg-ir-carbon hover:bg-ir-carbon text-ir-bone text-xs font-semibold">
                        View PO History
                    </a>
                    <a href="{{ route('suppliers.create_po', $sup) }}" class="flex-1 py-2 text-center rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone text-xs font-semibold">
                        + Restock PO
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-3 text-center py-10 text-ir-copper">No suppliers found.</div>
        @endforelse
    </div>

</div>
@endsection
