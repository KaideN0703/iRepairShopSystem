@extends('layouts.app')

@section('title', 'Invoices & Billing')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-ir-carbon border border-ir-copper p-5 rounded-md">
        <form action="{{ route('invoices.index') }}" method="GET" class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-3 w-full sm:w-auto">
            <input type="text" name="search" value="{{ $search }}" placeholder="Search invoice #, ticket #, customer..." class="px-4 py-2 rounded-md bg-ir-void border border-ir-copper text-sm text-ir-bone focus:outline-none">
            
            <select name="status" class="px-4 py-2 rounded-md bg-ir-void border border-ir-copper text-sm text-ir-bone focus:outline-none">
                <option value="">All Payment Statuses</option>
                <option value="paid" {{ $status === 'paid' ? 'selected' : '' }}>Paid</option>
                <option value="partial" {{ $status === 'partial' ? 'selected' : '' }}>Partial</option>
                <option value="unpaid" {{ $status === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
            </select>

            <button type="submit" class="py-2 px-4 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone font-medium text-sm transition-colors">
                <i class="fa-solid fa-filter mr-1"></i> Filter
            </button>
        </form>
    </div>

    <div class="bg-ir-carbon border border-ir-copper rounded-md overflow-hidden shadow-lg">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-ir-bone">
                <thead class="bg-ir-void text-xs font-semibold text-ir-bone/70 uppercase tracking-wider border-b border-ir-copper">
                    <tr>
                        <th class="px-6 py-4">Invoice #</th>
                        <th class="px-6 py-4">Job Ticket</th>
                        <th class="px-6 py-4">Customer</th>
                        <th class="px-6 py-4">Date Issued</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-right">Paid / Total</th>
                        <th class="px-6 py-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ir-copper">
                    @forelse($invoices as $inv)
                        <tr class="hover:bg-ir-carbon/50 transition-colors">
                            <td class="px-6 py-4 font-bold text-ir-bone font-mono">
                                <a href="{{ route('invoices.show', $inv) }}" class="text-ir-gold hover:underline">
                                    {{ $inv->invoice_number }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-xs font-semibold text-ir-bone">
                                #{{ $inv->jobOrder?->ticket_number }} ({{ $inv->jobOrder?->device?->brand }} {{ $inv->jobOrder?->device?->model }})
                            </td>
                            <td class="px-6 py-4 text-xs font-medium text-ir-bone">
                                {{ $inv->customer?->name }}
                            </td>
                            <td class="px-6 py-4 text-xs text-ir-bone/70">
                                {{ $inv->issue_date ? $inv->issue_date->format('M d, Y') : 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <x-status-badge :stage="$inv->payment_status" />
                            </td>
                            <td class="px-6 py-4 text-right font-mono">
                                <span class="font-bold text-ir-bone"><x-currency :amount="$inv->paid_amount" /></span> / <x-currency :amount="$inv->total_amount" />
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('invoices.show', $inv) }}" class="p-2 rounded-lg bg-ir-carbon hover:bg-ir-carbon text-ir-bone hover:text-ir-bone" title="View Invoice">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a href="{{ route('invoices.pdf', $inv) }}" class="p-2 rounded-lg bg-ir-carbon hover:bg-ir-carbon text-ir-bone hover:text-ir-bone" title="Download PDF">
                                        <i class="fa-solid fa-file-pdf"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-ir-copper">No invoices found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($invoices->hasPages())
            <div class="px-6 py-4 border-t border-ir-copper bg-ir-void">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
