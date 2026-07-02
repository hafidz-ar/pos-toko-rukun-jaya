<?php

namespace App\Http\Controllers;

use App\Models\Backup;
use App\Models\Notification;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    /**
     * List available backups.
     */
    public function index()
    {
        $backups = Backup::orderByDesc('created_at')
            ->limit(7)
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'filename' => $b->filename,
                'file_size' => $this->formatBytes($b->file_size),
                'created_at' => $b->created_at->format('d/m/Y H:i'),
            ]);

        return response()->json($backups);
    }

    /**
     * Create a new backup.
     */
    public function create(Request $request)
    {
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');
        $dbHost = config('database.connections.mysql.host');
        $dbPort = config('database.connections.mysql.port');

        $filename = 'backup_' . date('Y-m-d_His') . '.sql';
        $backupDir = storage_path('app/backups');

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $filepath = $backupDir . DIRECTORY_SEPARATOR . $filename;

        // Build mysqldump command
        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s %s %s > %s',
            escapeshellarg($dbHost),
            escapeshellarg($dbPort),
            escapeshellarg($dbUser),
            $dbPass ? '--password=' . escapeshellarg($dbPass) : '',
            escapeshellarg($dbName),
            escapeshellarg($filepath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($filepath)) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat backup database.',
            ], 500);
        }

        $backup = Backup::create([
            'filename' => $filename,
            'filepath' => $filepath,
            'file_size' => filesize($filepath),
            'created_at' => now(),
        ]);

        // Notify owner
        $owners = User::where('role', 'owner')->where('is_active', true)->get();
        foreach ($owners as $owner) {
            Notification::create([
                'recipient_user_id' => $owner->id,
                'type' => 'backup',
                'is_anomaly' => false,
                'message' => 'Backup harian berhasil dibuat: ' . $filename . ' (' . $this->formatBytes($backup->file_size) . '). Klik untuk download.',
                'is_read' => false,
                'created_at' => now(),
            ]);

            if ($owner->telegram_chat_id) {
                try {
                    app(TelegramService::class)->sendDocument(
                        $owner->telegram_chat_id,
                        $filepath,
                        $filename,
                        '📦 <b>Backup harian berhasil dibuat!</b>'
                    );
                } catch (\Throwable $e) {
                    \Log::warning('Telegram backup notification failed: ' . $e->getMessage());
                }
            }
        }

        // Clean old backups (>7 days)
        $this->cleanOldBackups();

        return response()->json([
            'success' => true,
            'message' => 'Backup berhasil dibuat.',
            'backup' => [
                'id' => $backup->id,
                'filename' => $backup->filename,
                'file_size' => $this->formatBytes($backup->file_size),
            ],
        ]);
    }

    /**
     * Download a backup file.
     */
    public function download(Backup $backup)
    {
        if (!file_exists($backup->filepath)) {
            return back()->with('error', 'File backup tidak ditemukan.');
        }

        return response()->download($backup->filepath, $backup->filename);
    }

    /**
     * Restore database from uploaded backup file.
     */
    public function restore(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file|mimes:sql,txt',
            'confirmation' => 'required|in:PULIHKAN',
        ]);

        $file = $request->file('backup_file');
        $tempPath = $file->storeAs('temp', 'restore_' . time() . '.sql');
        $fullPath = storage_path('app/' . $tempPath);

        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');
        $dbHost = config('database.connections.mysql.host');
        $dbPort = config('database.connections.mysql.port');

        $command = sprintf(
            'mysql --host=%s --port=%s --user=%s %s %s < %s',
            escapeshellarg($dbHost),
            escapeshellarg($dbPort),
            escapeshellarg($dbUser),
            $dbPass ? '--password=' . escapeshellarg($dbPass) : '',
            escapeshellarg($dbName),
            escapeshellarg($fullPath)
        );

        exec($command, $output, $returnCode);

        // Clean up temp file
        @unlink($fullPath);

        if ($returnCode !== 0) {
            return back()->with('error', 'Gagal memulihkan database. Pastikan file backup valid.');
        }

        return back()->with('success', 'Database berhasil dipulihkan dari backup.');
    }

    /**
     * Clean old backup files (retention: 7 days).
     */
    private function cleanOldBackups(): void
    {
        $oldBackups = Backup::where('created_at', '<', now()->subDays(7))->get();

        foreach ($oldBackups as $backup) {
            if (file_exists($backup->filepath)) {
                @unlink($backup->filepath);
            }
            $backup->delete();
        }
    }

    /**
     * Format bytes to human readable.
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
