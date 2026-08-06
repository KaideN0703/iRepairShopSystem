@extends('layouts.app')

@section('title', 'Edit User Account')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-ir-bone">Edit User Account</h2>
        <a href="{{ route('users.index') }}" class="px-4 py-2 rounded-md bg-ir-carbon text-ir-bone text-sm">Cancel</a>
    </div>

    <form action="{{ route('users.update', $user) }}" method="POST" class="bg-ir-carbon border border-ir-copper rounded-md p-6 space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label for="name" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Full Name *</label>
            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="email" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Email Address</label>
                <input type="email" id="email" readonly value="{{ $user->email }}" class="w-full px-4 py-3 rounded-md bg-ir-void/60 border border-ir-copper text-ir-bone/70 text-sm">
            </div>

            <div>
                <label for="phone" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Phone Number</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="password" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">New Password (Leave blank to keep)</label>
                <input type="password" id="password" name="password" class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm">
            </div>

            <div>
                <label for="role" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Role & Permissions *</label>
                <select id="role" name="role" required class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm">
                    @foreach($roles as $r)
                        <option value="{{ $r->name }}" {{ $user->hasRole($r->name) ? 'selected' : '' }}>{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="flex items-center gap-2 cursor-pointer text-ir-bone text-sm">
                <input type="checkbox" name="is_active" value="1" {{ $user->is_active ? 'checked' : '' }} class="rounded bg-ir-void border-ir-copper text-indigo-600">
                <span>Account Active Status</span>
            </label>
        </div>

        <div class="pt-4 border-t border-ir-copper flex justify-end">
            <button type="submit" class="px-6 py-3 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone font-semibold text-sm transition-colors">
                Update User Account
            </button>
        </div>
    </form>
</div>
@endsection
