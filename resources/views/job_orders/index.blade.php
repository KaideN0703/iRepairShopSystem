@extends('layouts.app')

@section('title', 'Repair Tickets (Job Orders)')

@section('content')
<div class="space-y-6">

    <!-- Header Actions & Search Filters -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-ir-carbon border border-ir-copper p-5 rounded-md">
        <form action="{{ route('job_orders.index') }}" method="GET" class="w-full sm:w-auto flex-1 grid grid-cols-1 sm:grid-cols-4 gap-3">
            <input type="text" name="search" value="{{ $search }}" placeholder="Search ticket #, issue, customer..." class="px-4 py-2 rounded-md bg-ir-void border border-ir-copper text-sm text-ir-bone focus:outline-none focus:border-ir-gold">
            
            <select name="status" class="px-4 py-2 rounded-md bg-ir-void border border-ir-copper text-sm text-ir-bone focus:outline-none focus:border-ir-gold">
                <option value="">All Statuses</option>
                @foreach($stages as $stage)
                    <option value="{{ $stage }}" {{ $status === $stage ? 'selected' : '' }}>{{ $stage }}</option>
                @endforeach
            </select>

            <select name="priority" class="px-4 py-2 rounded-md bg-ir-void border border-ir-copper text-sm text-ir-bone focus:outline-none focus:border-ir-gold">
                <option value="">All Priorities</option>
                <option value="Low" {{ $priority === 'Low' ? 'selected' : '' }}>Low</option>
                <option value="Normal" {{ $priority === 'Normal' ? 'selected' : '' }}>Normal</option>
                <option value="High" {{ $priority === 'High' ? 'selected' : '' }}>High</option>
                <option value="Urgent" {{ $priority === 'Urgent' ? 'selected' : '' }}>Urgent</option>
            </select>

            <button type="submit" class="py-2 px-4 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone font-medium text-sm transition-colors">
                <i class="fa-solid fa-filter mr-1"></i> Filter
            </button>
        </form>

        <a href="{{ route('job_orders.create') }}" class="w-full sm:w-auto py-2.5 px-5 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone font-semibold text-sm transition-colors flex items-center justify-center gap-2">
            <i class="fa-solid fa-plus"></i> New Ticket
        </a>
    </div>

    <!-- Job Orders Data Table -->
    <div class="bg-ir-carbon border border-ir-copper rounded-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-ir-bone">
                <thead class="bg-ir-void text-xs font-semibold text-ir-bone/70 uppercase tracking-wider border-b border-ir-copper">
                    <tr>
                        <th class="px-6 py-4">Ticket #</th>
                        <th class="px-6 py-4">Customer & Device</th>
                        <th class="px-6 py-4">Technician</th>
                        <th class="px-6 py-4">Status Pipeline</th>
                        <th class="px-6 py-4">Priority</th>
                        <th class="px-6 py-4">Est. Completion</th>
                        <th class="px-6 py-4 text-right">Total Cost</th>
                        <th class="px-6 py-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ir-copper">
                    @forelse($jobOrders as $job)
                        <tr class="hover:bg-ir-carbon/50 transition-colors">
                            <td class="px-6 py-4 font-bold text-ir-bone">
                                <a href="{{ route('job_orders.show', $job) }}" class="text-ir-gold hover:underline">
                                    {{ $job->ticket_number }}
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                <span class="block font-semibold text-ir-bone">{{ $job->customer?->name }}</span>
                                <span class="block text-xs text-ir-bone/70">{{ $job->device?->brand }} {{ $job->device?->model }} ({{ $job->device?->color ?? 'N/A' }})</span>
                            </td>
                            <td class="px-6 py-4">
                                @if($job->technician)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-ir-carbon text-xs text-ir-amber-deep border border-ir-copper">
                                        <i class="fa-solid fa-user-gear"></i> {{ $job->technician->name }}
                                    </span>
                                @else
                                    <span class="text-xs text-ir-copper italic">Unassigned</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $badgeStyle = match($job->status) {
                                        'Received' => 'bg-ir-carbon text-ir-bone border-ir-copper',
                                        'Diagnosing' => 'bg-ir-amber-deep/10 text-ir-gold border-ir-gold/30',
                                        'Waiting for Parts' => 'bg-amber-500/10 text-amber-400 border-amber-500/30',
                                        'Under Repair' => 'bg-blue-500/10 text-blue-400 border-blue-500/30',
                                        'Testing' => 'bg-purple-500/10 text-purple-400 border-purple-500/30',
                                        'Ready for Pickup' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
                                        'Completed' => 'bg-green-500/10 text-green-400 border-green-500/30',
                                        'Released' => 'bg-teal-500/10 text-teal-400 border-teal-500/30',
                                        default => 'bg-ir-carbon text-ir-bone'
                                    };
                                @endphp
                                <span class="px-3 py-1 rounded-full text-xs font-semibold border {{ $badgeStyle }}">
                                    {{ $job->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $prioColor = match($job->priority) {
                                        'Urgent' => 'text-red-400 font-bold',
                                        'High' => 'text-amber-400 font-semibold',
                                        'Normal' => 'text-ir-bone',
                                        'Low' => 'text-ir-copper',
                                        default => 'text-ir-bone'
                                    };
                                @endphp
                                <span class="text-xs {{ $prioColor }}">{{ $job->priority }}</span>
                            </td>
                            <td class="px-6 py-4 text-xs text-ir-bone/70">
                                {{ $job->estimated_completion_date ? $job->estimated_completion_date->format('M d, Y') : 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-ir-bone">
                                ₱{{ number_format($job->total_cost, 2) }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('job_orders.show', $job) }}" class="p-2 rounded-lg bg-ir-carbon hover:bg-ir-carbon text-ir-bone hover:text-ir-bone transition-colors" title="View Details">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a href="{{ route('job_orders.receipt', $job) }}" target="_blank" class="p-2 rounded-lg bg-ir-carbon hover:bg-ir-carbon text-ir-bone hover:text-ir-bone transition-colors" title="Print Receipt">
                                        <i class="fa-solid fa-print"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-10 text-center text-ir-copper">
                                <i class="fa-solid fa-ticket text-4xl mb-3"></i>
                                <p>No repair job orders found matching criteria.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($jobOrders->hasPages())
            <div class="px-6 py-4 border-t border-ir-copper bg-ir-void">
                {{ $jobOrders->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
