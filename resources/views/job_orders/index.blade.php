@extends('layouts.app')

@section('title', 'Repair Tickets (Job Orders)')

@section('content')
<div class="space-y-6">

    <!-- Header Actions & Search Filters -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-ir-carbon border border-ir-copper p-5 rounded-md">
        <form action="{{ route('job_orders.index') }}" method="GET" class="w-full sm:w-auto flex-1 grid grid-cols-1 sm:grid-cols-4 gap-3">
            <input type="text" name="search" value="{{ $search }}" placeholder="Search ticket #, customer name, device..." class="px-4 py-2 rounded-md bg-ir-void border border-ir-copper text-sm text-ir-bone focus:outline-none focus:ring-2 focus:ring-ir-gold/50 focus:border-ir-gold">
            
            <select name="status" class="px-4 py-2 rounded-md bg-ir-void border border-ir-copper text-sm text-ir-bone focus:outline-none focus:ring-2 focus:ring-ir-gold/50 focus:border-ir-gold">
                <option value="">All Pipeline Statuses</option>
                @foreach($stages as $stage)
                    <option value="{{ $stage }}" {{ $status === $stage ? 'selected' : '' }}>{{ $stage }}</option>
                @endforeach
            </select>

            <select name="priority" class="px-4 py-2 rounded-md bg-ir-void border border-ir-copper text-sm text-ir-bone focus:outline-none focus:ring-2 focus:ring-ir-gold/50 focus:border-ir-gold">
                <option value="">All Priorities</option>
                <option value="Low" {{ $priority === 'Low' ? 'selected' : '' }}>Low Priority</option>
                <option value="Normal" {{ $priority === 'Normal' ? 'selected' : '' }}>Normal Priority</option>
                <option value="High" {{ $priority === 'High' ? 'selected' : '' }}>High Priority</option>
                <option value="Urgent" {{ $priority === 'Urgent' ? 'selected' : '' }}>Urgent Priority</option>
            </select>

            <button type="submit" class="py-2 px-4 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone font-semibold text-sm transition-colors flex items-center justify-center gap-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-ir-gold/50">
                <i class="fa-solid fa-sliders"></i> Apply Filters
            </button>
        </form>

        <a href="{{ route('job_orders.create') }}" class="w-full sm:w-auto py-2.5 px-5 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone font-bold text-sm transition-colors flex items-center justify-center gap-2 shadow-sm shrink-0">
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
                        <th class="px-6 py-4">Assigned Technician</th>
                        <th class="px-6 py-4">Status Pipeline</th>
                        <th class="px-6 py-4">Priority</th>
                        <th class="px-6 py-4">Est. Completion</th>
                        <th class="px-6 py-4 text-right">Total Cost</th>
                        <th class="px-6 py-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ir-copper">
                    @forelse($jobOrders as $job)
                        <tr class="hover:bg-ir-carbon/80 transition-colors group">
                            <td class="px-6 py-4 font-bold text-ir-bone">
                                <a href="{{ route('job_orders.show', $job) }}" class="text-ir-gold group-hover:underline font-mono">
                                    #{{ $job->ticket_number }}
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                <span class="block font-semibold text-ir-bone">{{ $job->customer?->name }}</span>
                                <span class="block text-xs text-ir-bone/70">{{ $job->device?->brand }} {{ $job->device?->model }} ({{ $job->device?->color ?? 'N/A' }})</span>
                            </td>
                            <td class="px-6 py-4">
                                @if($job->technician)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-ir-void text-xs text-ir-amber-deep border border-ir-copper">
                                        <i class="fa-solid fa-user-gear text-[10px]"></i> {{ $job->technician->name }}
                                    </span>
                                @else
                                    <span class="text-xs text-ir-copper italic">Unassigned</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <x-status-badge :stage="$job->status" />
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $prioColor = match($job->priority) {
                                        'Urgent' => 'text-red-400 font-bold',
                                        'High' => 'text-amber-400 font-semibold',
                                        'Normal' => 'text-ir-bone/80',
                                        'Low' => 'text-ir-copper',
                                        default => 'text-ir-bone/80'
                                    };
                                @endphp
                                <span class="text-xs uppercase tracking-wide {{ $prioColor }}">{{ $job->priority }}</span>
                            </td>
                            <td class="px-6 py-4 text-xs text-ir-bone/70 font-mono">
                                {{ $job->estimated_completion_date ? $job->estimated_completion_date->format('M d, Y') : 'Pending Inspection' }}
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-ir-bone">
                                <x-currency :amount="$job->total_cost" />
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('job_orders.show', $job) }}" class="p-2 rounded-lg bg-ir-void hover:bg-ir-gold/20 hover:text-ir-gold text-ir-bone/70 transition-colors border border-ir-copper/40" title="Open Job Order Workspace">
                                        <i class="fa-solid fa-arrow-right-to-bracket"></i>
                                    </a>
                                    <a href="{{ route('job_orders.receipt', $job) }}" target="_blank" class="p-2 rounded-lg bg-ir-void hover:bg-ir-gold/20 hover:text-ir-gold text-ir-bone/70 transition-colors border border-ir-copper/40" title="Print Intake Receipt">
                                        <i class="fa-solid fa-print"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center bg-ir-void/40">
                                <div class="max-w-md mx-auto space-y-3">
                                    <div class="w-12 h-12 rounded-full bg-ir-carbon border border-ir-copper text-ir-gold flex items-center justify-center mx-auto text-xl shadow-inner">
                                        <i class="fa-solid fa-ticket-simple"></i>
                                    </div>
                                    <h4 class="text-sm font-bold text-ir-bone">No repair job orders match your criteria</h4>
                                    <p class="text-xs text-ir-bone/60 leading-relaxed">
                                        @if($search || $status || $priority)
                                            We couldn't find any tickets matching your search or filter parameters. Try clearing your filters or searching by exact ticket number.
                                        @else
                                            There are currently no repair tickets logged in the system. Get started by creating your first job order intake.
                                        @endif
                                    </p>
                                    <div class="pt-2 flex items-center justify-center gap-3">
                                        @if($search || $status || $priority)
                                            <a href="{{ route('job_orders.index') }}" class="btn-secondary btn-sm">
                                                <i class="fa-solid fa-rotate-left"></i> Reset Filters
                                            </a>
                                        @endif
                                        <a href="{{ route('job_orders.create') }}" class="btn-primary btn-sm">
                                            <i class="fa-solid fa-plus"></i> Create New Ticket
                                        </a>
                                    </div>
                                </div>
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

