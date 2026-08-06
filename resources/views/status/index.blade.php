@extends('layouts.public')

@section('title', 'Track Your Repair Status')

@section('content')
<div class="max-w-2xl mx-auto text-center space-y-8 py-10">

    <div class="space-y-3">
        <div class="w-16 h-16 rounded-md bg-ir-carbon border border-ir-copper mx-auto flex items-center justify-center text-ir-gold">
            <i class="fa-solid fa-magnifying-glass text-2xl"></i>
        </div>
        <h1 class="text-3xl font-extrabold text-ir-bone">Track Device Repair Status Live</h1>
        <p class="text-sm text-ir-bone/70 max-w-md mx-auto">
            Enter your repair ticket number (e.g. <strong class="text-ir-amber-deep font-mono">JO-2026-0001</strong>), invoice number (<strong class="text-ir-amber-deep font-mono">INV-2026-0001</strong>), device serial number, or phone number to view live repair progress.
        </p>
    </div>

    @if(session('error'))
        <div class="p-4 rounded-md bg-red-500/10 border border-red-500/30 text-red-300 text-sm max-w-md mx-auto">
            <i class="fa-solid fa-circle-exclamation mr-1"></i> {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('status.lookup') }}" method="POST" class="max-w-md mx-auto space-y-4">
        @csrf

        <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-ir-copper"><i class="fa-solid fa-ticket"></i></span>
            <input type="text" name="ticket_number" value="{{ old('ticket_number') }}" placeholder="Enter ticket #, invoice #, or phone number..." required class="w-full pl-11 pr-4 py-4 rounded-md bg-ir-carbon border border-ir-copper text-ir-bone text-base font-mono focus:outline-none focus:border-ir-gold">
        </div>

        <button type="submit" class="w-full py-4 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone font-bold text-sm transition-colors">
            Check Repair Status Now
        </button>
    </form>

    <!-- Quick Demo Ticket Buttons for Testing -->
    <div class="pt-6 border-t border-ir-copper max-w-md mx-auto">
        <span class="block text-xs font-semibold text-ir-copper uppercase tracking-wider mb-3">Quick Demo Tickets</span>
        <div class="flex flex-wrap justify-center gap-2 text-xs">
            <a href="{{ route('status.show', 'JO-2026-0001') }}" class="px-3 py-1.5 rounded-lg bg-ir-carbon border border-ir-copper text-ir-gold hover:border-ir-gold font-mono">
                JO-2026-0001 (Under Repair)
            </a>
            <a href="{{ route('status.show', 'JO-2026-0002') }}" class="px-3 py-1.5 rounded-lg bg-ir-carbon border border-ir-copper text-emerald-400 hover:border-ir-gold font-mono">
                JO-2026-0002 (Ready for Pickup)
            </a>
            <a href="{{ route('status.show', 'JO-2026-0004') }}" class="px-3 py-1.5 rounded-lg bg-ir-carbon border border-ir-copper text-teal-400 hover:border-ir-gold font-mono">
                JO-2026-0004 (Released)
            </a>
        </div>
    </div>

</div>
@endsection
