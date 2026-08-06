@extends('layouts.app')

@section('title', 'Customer Management')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-ir-carbon border border-ir-copper p-5 rounded-md">
        <form action="{{ route('customers.index') }}" method="GET" class="flex-1 flex gap-3 w-full sm:w-auto">
            <input type="text" name="search" value="{{ $search }}" placeholder="Search customer name, email, phone, code..." class="w-full sm:max-w-md px-4 py-2 rounded-md bg-ir-void border border-ir-copper text-sm text-ir-bone focus:outline-none focus:border-ir-gold">
            <button type="submit" class="px-4 py-2 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone font-medium text-sm">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
        </form>

        <a href="{{ route('customers.create') }}" class="w-full sm:w-auto px-5 py-2.5 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone font-semibold text-sm flex items-center justify-center gap-2 transition-colors">
            <i class="fa-solid fa-plus"></i> Add New Customer
        </a>
    </div>

    <!-- Customers Table -->
    <div class="bg-ir-carbon border border-ir-copper rounded-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-ir-bone">
                <thead class="bg-ir-void text-xs font-semibold text-ir-bone/70 uppercase tracking-wider border-b border-ir-copper">
                    <tr>
                        <th class="px-6 py-4">Customer Code</th>
                        <th class="px-6 py-4">Name</th>
                        <th class="px-6 py-4">Phone & Email</th>
                        <th class="px-6 py-4">Address</th>
                        <th class="px-6 py-4 text-center">Devices</th>
                        <th class="px-6 py-4 text-center">Job Orders</th>
                        <th class="px-6 py-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ir-copper">
                    @forelse($customers as $c)
                        <tr class="hover:bg-ir-carbon/50 transition-colors">
                            <td class="px-6 py-4 font-mono font-bold text-ir-gold">
                                <a href="{{ route('customers.show', $c) }}" class="hover:underline">{{ $c->customer_code }}</a>
                            </td>
                            <td class="px-6 py-4 font-bold text-ir-bone">
                                {{ $c->name }}
                            </td>
                            <td class="px-6 py-4 text-xs">
                                <span class="block text-ir-bone"><i class="fa-solid fa-phone text-ir-copper w-4"></i> {{ $c->phone }}</span>
                                <span class="block text-ir-bone/70"><i class="fa-solid fa-envelope text-ir-copper w-4"></i> {{ $c->email ?? 'N/A' }}</span>
                            </td>
                            <td class="px-6 py-4 text-xs text-ir-bone/70 max-w-xs truncate">
                                {{ $c->address ?? 'No address' }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2.5 py-1 rounded-full bg-ir-carbon text-xs font-bold text-ir-bone border border-ir-copper">
                                    {{ $c->devices_count }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2.5 py-1 rounded-full bg-ir-amber-deep/10 text-xs font-bold text-ir-gold border border-ir-gold/30">
                                    {{ $c->job_orders_count }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('customers.show', $c) }}" class="p-2 rounded-lg bg-ir-carbon hover:bg-ir-carbon text-ir-bone hover:text-ir-bone" title="View Profile">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a href="{{ route('customers.edit', $c) }}" class="p-2 rounded-lg bg-ir-carbon hover:bg-ir-carbon text-ir-bone hover:text-ir-bone" title="Edit Customer">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-ir-copper">No customer profiles found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customers->hasPages())
            <div class="px-6 py-4 border-t border-ir-copper bg-ir-void">
                {{ $customers->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
