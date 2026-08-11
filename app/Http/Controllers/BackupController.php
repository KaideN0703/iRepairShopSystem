<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Backup;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;

class BackupController extends Controller
{
    public function index()
    {
        $this->authorize('backup.manage');
        $backups = Backup::latest()->get();
        return view('backup.index', compact('backups'));
    }

    public function createBackup(Request $request)
    {
        $this->authorize('backup.manage');

        $dbPath = database_path('database.sqlite');
        if (!File::exists($dbPath)) {
            return back()->with('error', 'SQLite database file does not exist.');
        }

        $filename = 'backup_irepair_' . date('Y-m-d_H-i-s') . '.sqlite';
        $destinationPath = storage_path('app/backups/' . $filename);

        File::ensureDirectoryExists(storage_path('app/backups'));
        File::copy($dbPath, $destinationPath);

        $size = File::size($destinationPath);

        $backup = Backup::create([
            'filename' => $filename,
            'file_path' => 'backups/' . $filename,
            'file_size' => $size,
            'type' => 'manual',
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name,
            'action' => 'database_backup',
            'module' => 'System',
            'description' => "Created manual database backup snapshot {$filename}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', "Database backup '{$filename}' created successfully!");
    }

    public function download(Backup $backup)
    {
        $this->authorize('backup.manage');

        $filePath = storage_path('app/' . $backup->file_path);
        if (File::exists($filePath)) {
            return response()->download($filePath);
        }
        return back()->with('error', 'Backup file not found on disk.');
    }

    public function restore(Request $request, Backup $backup)
    {
        $this->authorize('backup.manage');

        $backupPath = storage_path('app/' . $backup->file_path);
        $dbPath = database_path('database.sqlite');

        if (!File::exists($backupPath)) {
            return back()->with('error', 'Backup file not found.');
        }

        // Copy backup over database.sqlite
        File::copy($backupPath, $dbPath);

        AuditLog::create([
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name,
            'action' => 'database_restore',
            'module' => 'System',
            'description' => "Restored database from snapshot {$backup->filename}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', "Database restored successfully from backup '{$backup->filename}'!");
    }
}
