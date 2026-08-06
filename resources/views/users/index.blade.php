@extends('layouts.app')

@section('title', 'Staff User & Role Management')

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between bg-ir-carbon border border-ir-copper p-5 rounded-md">
        <div>
            <h2 class="text-xl font-bold text-ir-bone">Staff User Accounts & Role Permissions</h2>
            <p class="text-xs text-ir-bone/70 mt-1">Manage staff user access for Administrator, Shop Manager, Technician, Inventory Staff, and Cashier</p>
        </div>

        <a href="{{ route('users.create') }}" class="px-5 py-2.5 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone font-semibold text-sm flex items-center gap-2 transition-colors">
            <i class="fa-solid fa-plus"></i> Add New Staff
        </a>
    </div>

    <div class="bg-ir-carbon border border-ir-copper rounded-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-ir-bone">
                <thead class="bg-ir-void text-xs font-semibold text-ir-bone/70 uppercase tracking-wider border-b border-ir-copper">
                    <tr>
                        <th class="px-6 py-4">User Name</th>
                        <th class="px-6 py-4">Email</th>
                        <th class="px-6 py-4">Phone</th>
                        <th class="px-6 py-4 text-center">Assigned Role</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ir-copper">
                    @foreach($users as $user)
                        <tr class="hover:bg-ir-carbon/50 transition-colors">
                            <td class="px-6 py-4 font-bold text-ir-bone flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-ir-carbon border border-ir-copper flex items-center justify-center font-bold text-ir-gold text-xs">
                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                </div>
                                <span>{{ $user->name }}</span>
                            </td>
                            <td class="px-6 py-4 text-ir-bone">{{ $user->email }}</td>
                            <td class="px-6 py-4 text-ir-bone/70 text-xs">{{ $user->phone ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-ir-amber-deep/10 text-ir-gold border border-ir-gold/30">
                                    {{ $user->roles->first()?->name ?? 'Staff' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $user->is_active ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20' }}">
                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <a href="{{ route('users.edit', $user) }}" class="p-2 rounded-lg bg-ir-carbon hover:bg-ir-carbon text-ir-bone hover:text-ir-bone" title="Edit User">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
