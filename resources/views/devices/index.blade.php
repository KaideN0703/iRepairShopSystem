@extends('layouts.app')

@section('title', 'Device Tracking')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-ir-carbon border border-ir-copper p-5 rounded-md">
        <form action="{{ route('devices.index') }}" method="GET" class="flex-1 flex gap-3 w-full sm:w-auto">
            <input type="text" name="search" value="{{ $search }}" placeholder="Search brand, model, serial #, owner..." class="w-full sm:max-w-md px-4 py-2 rounded-md bg-ir-void border border-ir-copper text-sm text-ir-bone focus:outline-none focus:border-ir-gold">
            <button type="submit" class="px-4 py-2 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone font-medium text-sm">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
        </form>

        @canany(['devices.create', 'customers.manage', 'jobs.create'])
        <a href="{{ route('devices.create') }}" class="w-full sm:w-auto px-5 py-2.5 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone font-semibold text-sm flex items-center justify-center gap-2 transition-colors">
            <i class="fa-solid fa-plus"></i> Register New Device
        </a>
        @endcanany
    </div>

    <!-- Table -->
    <div class="bg-ir-carbon border border-ir-copper rounded-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-ir-bone">
                <thead class="bg-ir-void text-xs font-semibold text-ir-bone/70 uppercase tracking-wider border-b border-ir-copper">
                    <tr>
                        <th class="px-6 py-4">Brand & Model</th>
                        <th class="px-6 py-4">Category</th>
                        <th class="px-6 py-4">Serial Number</th>
                        <th class="px-6 py-4">Customer Owner</th>
                        <th class="px-6 py-4 text-center">Passcode/Pattern</th>
                        <th class="px-6 py-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ir-copper">
                    @forelse($devices as $d)
                        <tr class="hover:bg-ir-carbon/50 transition-colors">
                            <td class="px-6 py-4 font-bold text-ir-bone">
                                <a href="{{ route('devices.show', $d) }}" class="text-ir-gold hover:underline">
                                    {{ $d->brand }} {{ $d->model }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-xs text-ir-bone">{{ $d->device_type }}</td>
                            <td class="px-6 py-4 text-xs font-mono text-ir-bone/70">{{ $d->serial_number ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-xs font-semibold text-ir-bone">
                                {{ $d->customer?->name }}
                            </td>
                            <td class="px-6 py-4 text-center text-xs font-mono text-amber-400">
                                {{ $d->passcode_pattern ?? 'None' }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('devices.show', $d) }}" class="p-2 rounded-lg bg-ir-carbon hover:bg-ir-carbon text-ir-bone hover:text-ir-bone" title="View Device">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    @can('customers.manage')
                                    <a href="{{ route('devices.edit', $d) }}" class="p-2 rounded-lg bg-ir-carbon hover:bg-ir-carbon text-ir-bone hover:text-ir-bone" title="Edit Device">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-ir-copper">No registered devices found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($devices->hasPages())
            <div class="px-6 py-4 border-t border-ir-copper bg-ir-void">
                {{ $devices->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
