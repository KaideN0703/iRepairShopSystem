@extends('layouts.app')

@section('title', 'Database Backup & Restore')

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between bg-ir-carbon border border-ir-copper p-5 rounded-md">
        <div>
            <h2 class="text-xl font-bold text-ir-bone">Database Snapshots & Disaster Recovery</h2>
            <p class="text-xs text-ir-bone/70 mt-1">Generate automated or manual SQLite database snapshots and restore points</p>
        </div>

        <form action="{{ route('backups.create') }}" method="POST">
            @csrf
            <button type="submit" class="px-5 py-2.5 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone font-semibold text-sm flex items-center gap-2 transition-colors">
                <i class="fa-solid fa-floppy-disk"></i> Create New Backup Snapshot
            </button>
        </form>
    </div>

    <!-- Backups Table -->
    <div class="bg-ir-carbon border border-ir-copper rounded-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-ir-bone">
                <thead class="bg-ir-void text-xs font-semibold text-ir-bone/70 uppercase tracking-wider border-b border-ir-copper">
                    <tr>
                        <th class="px-6 py-4">Snapshot Filename</th>
                        <th class="px-6 py-4">Backup Type</th>
                        <th class="px-6 py-4 text-center">File Size</th>
                        <th class="px-6 py-4 text-center">Created At</th>
                        <th class="px-6 py-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ir-copper">
                    @forelse($backups as $b)
                        <tr class="hover:bg-ir-carbon/50 transition-colors">
                            <td class="px-6 py-4 font-mono font-bold text-ir-gold">
                                {{ $b->filename }}
                            </td>
                            <td class="px-6 py-4 text-xs">
                                <span class="px-2.5 py-0.5 rounded-full font-bold uppercase text-[10px] bg-ir-carbon border border-ir-copper text-ir-bone">
                                    {{ $b->type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center font-mono text-xs text-ir-bone/70">
                                {{ round($b->file_size / 1024, 1) }} KB
                            </td>
                            <td class="px-6 py-4 text-center text-xs text-ir-bone/70">
                                {{ $b->created_at->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('backups.download', $b) }}" class="px-3 py-1.5 rounded-lg bg-ir-carbon hover:bg-ir-carbon text-ir-bone text-xs font-semibold" title="Download File">
                                        <i class="fa-solid fa-download mr-1"></i> Download
                                    </a>
                                    <form action="{{ route('backups.restore', $b) }}" method="POST" onsubmit="return confirm('WARNING: Restoring will overwrite the current active database with this backup snapshot. Continue?')">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-red-600/20 hover:bg-red-600 text-red-300 hover:text-ir-bone border border-red-500/30 text-xs font-semibold">
                                            <i class="fa-solid fa-rotate-left mr-1"></i> Restore
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-ir-copper">No database backup snapshots found. Click button above to generate one.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
