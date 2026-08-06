@extends('layouts.app')

@section('title', 'Create User Account')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-ir-bone">Create Staff User Account</h2>
        <a href="{{ route('users.index') }}" class="px-4 py-2 rounded-md bg-ir-carbon text-ir-bone text-sm">Cancel</a>
    </div>

    <form action="{{ route('users.store') }}" method="POST" class="bg-ir-carbon border border-ir-copper rounded-md p-6 space-y-4">
        @csrf

        <div>
            <label for="name" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Full Name *</label>
            <input type="text" id="name" name="name" required class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm focus:border-ir-gold focus:outline-none">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="email" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Email Address *</label>
                <input type="email" id="email" name="email" required class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm focus:border-ir-gold focus:outline-none">
            </div>

            <div>
                <label for="phone" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Phone Number</label>
                <input type="text" id="phone" name="phone" class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm focus:border-ir-gold focus:outline-none">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="password" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Password *</label>
                <input type="password" id="password" name="password" required class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm focus:border-ir-gold focus:outline-none">
            </div>

            <div>
                <label for="role" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Role & Permissions *</label>
                <select id="role" name="role" required class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm focus:border-ir-gold focus:outline-none">
                    @foreach($roles as $r)
                        <option value="{{ $r->name }}">{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="pt-4 border-t border-ir-copper flex justify-end">
            <button type="submit" class="px-6 py-3 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone font-semibold text-sm transition-colors">
                Create User Account
            </button>
        </div>
    </form>
</div>
@endsection
