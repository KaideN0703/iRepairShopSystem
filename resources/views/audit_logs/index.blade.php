@extends('layouts.app')

@section('title', 'Audit Trail & Activity Logs')

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between bg-ir-carbon border border-ir-copper p-5 rounded-md">
        <div>
            <h2 class="text-xl font-bold text-ir-bone">Security Audit Trail & System Activity Logs</h2>
            <p class="text-xs text-ir-bone/70 mt-1">Full immutable history of user logins, repair updates, inventory movements, and payments</p>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="bg-ir-carbon border border-ir-copper p-5 rounded-md">
        <form action="{{ route('audit_logs.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <input type="text" name="search" value="{{ $search }}" placeholder="Search user, action, IP..." class="px-4 py-2 rounded-md bg-ir-void border border-ir-copper text-sm text-ir-bone focus:outline-none">
            
            <select name="module" class="px-4 py-2 rounded-md bg-ir-void border border-ir-copper text-sm text-ir-bone focus:outline-none">
                <option value="">All Modules</option>
                <option value="Security" {{ $module === 'Security' ? 'selected' : '' }}>Security & Auth</option>
                <option value="JobOrders" {{ $module === 'JobOrders' ? 'selected' : '' }}>Job Orders</option>
                <option value="Customers" {{ $module === 'Customers' ? 'selected' : '' }}>Customers</option>
                <option value="Inventory" {{ $module === 'Inventory' ? 'selected' : '' }}>Inventory</option>
                <option value="Invoices" {{ $module === 'Invoices' ? 'selected' : '' }}>Invoices</option>
            </select>

            <select name="action" class="px-4 py-2 rounded-md bg-ir-void border border-ir-copper text-sm text-ir-bone focus:outline-none">
                <option value="">All Actions</option>
                <option value="login" {{ $action === 'login' ? 'selected' : '' }}>Login</option>
                <option value="create" {{ $action === 'create' ? 'selected' : '' }}>Create</option>
                <option value="update" {{ $action === 'update' ? 'selected' : '' }}>Update</option>
                <option value="status_change" {{ $action === 'status_change' ? 'selected' : '' }}>Status Transition</option>
                <option value="stock_adjust" {{ $action === 'stock_adjust' ? 'selected' : '' }}>Stock Adjust</option>
            </select>

            <button type="submit" class="py-2 px-4 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone font-medium text-sm transition-colors">
                <i class="fa-solid fa-filter mr-1"></i> Filter Logs
            </button>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="bg-ir-carbon border border-ir-copper rounded-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-ir-bone">
                <thead class="bg-ir-void text-xs font-semibold text-ir-bone/70 uppercase tracking-wider border-b border-ir-copper">
                    <tr>
                        <th class="px-6 py-4">Timestamp</th>
                        <th class="px-6 py-4">User</th>
                        <th class="px-6 py-4">Module</th>
                        <th class="px-6 py-4">Action</th>
                        <th class="px-6 py-4">Description</th>
                        <th class="px-6 py-4 text-right">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ir-copper">
                    @forelse($logs as $log)
                        <tr class="hover:bg-ir-carbon/50 transition-colors">
                            <td class="px-6 py-4 text-xs font-mono text-ir-bone/70">
                                {{ $log->created_at->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="px-6 py-4 font-bold text-ir-bone">
                                {{ $log->user_name ?? ($log->user?->name ?? 'System') }}
                            </td>
                            <td class="px-6 py-4 text-xs font-semibold text-ir-gold">
                                {{ $log->module }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-ir-carbon border border-ir-copper text-ir-bone">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs text-ir-bone">
                                {{ $log->description }}
                            </td>
                            <td class="px-6 py-4 text-right font-mono text-xs text-ir-copper">
                                {{ $log->ip_address ?? '127.0.0.1' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-ir-copper">No activity logs recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="px-6 py-4 border-t border-ir-copper bg-ir-void">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
