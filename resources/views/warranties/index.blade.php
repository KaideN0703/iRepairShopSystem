@extends('layouts.app')

@section('title', 'Warranties & Claims')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-ir-carbon border border-ir-copper p-5 rounded-md">
        <form action="{{ route('warranties.index') }}" method="GET" class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-3 w-full sm:w-auto">
            <input type="text" name="search" value="{{ $search }}" placeholder="Search customer, device, ticket #..." class="px-4 py-2 rounded-md bg-ir-void border border-ir-copper text-sm text-ir-bone focus:outline-none">
            
            <select name="status" class="px-4 py-2 rounded-md bg-ir-void border border-ir-copper text-sm text-ir-bone focus:outline-none">
                <option value="">All Warranty Statuses</option>
                <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                <option value="claimed" {{ $status === 'claimed' ? 'selected' : '' }}>Claimed</option>
                <option value="expired" {{ $status === 'expired' ? 'selected' : '' }}>Expired</option>
            </select>

            <button type="submit" class="py-2 px-4 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone font-medium text-sm transition-colors">
                <i class="fa-solid fa-filter mr-1"></i> Filter
            </button>
        </form>
    </div>

    <div class="bg-ir-carbon border border-ir-copper rounded-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-ir-bone">
                <thead class="bg-ir-void text-xs font-semibold text-ir-bone/70 uppercase tracking-wider border-b border-ir-copper">
                    <tr>
                        <th class="px-6 py-4">Job Ticket</th>
                        <th class="px-6 py-4">Customer & Device</th>
                        <th class="px-6 py-4">Coverage Period</th>
                        <th class="px-6 py-4 text-center">Expiry Date</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-center">Claims Count</th>
                        <th class="px-6 py-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ir-copper">
                    @forelse($warranties as $w)
                        <tr class="hover:bg-ir-carbon/50 transition-colors">
                            <td class="px-6 py-4 font-bold text-ir-gold font-mono">
                                <a href="{{ route('job_orders.show', $w->job_order_id) }}" class="hover:underline">
                                    #{{ $w->jobOrder?->ticket_number }}
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                <span class="block font-semibold text-ir-bone">{{ $w->customer?->name }}</span>
                                <span class="block text-xs text-ir-bone/70">{{ $w->device?->brand }} {{ $w->device?->model }}</span>
                            </td>
                            <td class="px-6 py-4 text-xs text-ir-bone">
                                {{ $w->warranty_period_days }} Days Parts & Labor
                            </td>
                            <td class="px-6 py-4 text-center text-xs font-mono">
                                {{ $w->end_date ? $w->end_date->format('M d, Y') : 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1 rounded-full text-xs font-bold border uppercase
                                    @if($w->status === 'active') bg-emerald-500/10 text-emerald-400 border-emerald-500/30
                                    @elseif($w->status === 'claimed') bg-amber-500/10 text-amber-400 border-amber-500/30
                                    @else bg-ir-carbon text-ir-bone/70 border-ir-copper @endif">
                                    {{ $w->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2.5 py-1 rounded-full bg-ir-carbon text-xs font-bold text-ir-bone border border-ir-copper">
                                    {{ $w->claims->count() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <a href="{{ route('warranties.show', $w) }}" class="p-2 rounded-lg bg-ir-carbon hover:bg-ir-carbon text-ir-bone hover:text-ir-bone" title="View & File Claim">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-ir-copper">No warranty records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($warranties->hasPages())
            <div class="px-6 py-4 border-t border-ir-copper bg-ir-void">
                {{ $warranties->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
