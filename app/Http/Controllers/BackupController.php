<?php

namespace App\Http\Controllers;

use App\Models\Backup;
use App\Models\Notification;
use App\Models\User;
use App\Services\TelegramService;
use App\Services\DatabaseBackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Throwable;

class BackupController extends Controller
{
    protected DatabaseBackupService $backupService;

    public function __construct(DatabaseBackupService $backupService)
    {
        $this->backupService = $backupService;
    }

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
                'status' => $b->status,
                'error_message' => $b->error_message,
                'created_at' => $b->created_at ? $b->created_at->format('d/m/Y H:i') : null,
            ]);

        return response()->json($backups);
    }

    /**
     * Create a new backup.
     */
    public function create(Request $request)
    {
        // 1. Create initial metadata in processing state
        $backup = Backup::create([
            'filename' => 'backup_pending_' . date('Y-m-d_His') . '.sql',
            'filepath' => '',
            'file_size' => 0,
            'status' => 'processing',
            'created_at' => now(),
        ]);

        try {
            // 2. Perform the backup using the Service
            $metadata = $this->backupService->backup();

            // 3. Update metadata to completed
            $backup->update([
                'filename' => $metadata['filename'],
                'filepath' => $metadata['filepath'],
                'file_size' => $metadata['file_size'],
                'status' => 'completed',
            ]);

            // Notify owners
            $owners = User::where('role', 'owner')->where('is_active', true)->get();
            foreach ($owners as $owner) {
                Notification::create([
                    'recipient_user_id' => $owner->id,
                    'type' => 'backup',
                    'is_anomaly' => false,
                    'message' => 'Backup database berhasil dibuat: ' . $backup->filename . ' (' . $this->formatBytes($backup->file_size) . ').',
                    'is_read' => false,
                    'created_at' => now(),
                ]);

                if ($owner->telegram_chat_id) {
                    try {
                        app(TelegramService::class)->sendDocument(
                            $owner->telegram_chat_id,
                            $backup->filepath,
                            $backup->filename,
                            '📦 <b>Backup harian berhasil dibuat!</b>'
                        );
                    } catch (Throwable $e) {
                        Log::warning('Telegram backup notification failed: ' . $e->getMessage());
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

        } catch (Throwable $e) {
            // 4. Update metadata to failed
            $backup->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Backup creation failed. ID: ' . $backup->id . ' | Error: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat backup database. Detail: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download a backup file.
     */
    public function download(Backup $backup)
    {
        if ($backup->status !== 'completed') {
            return back()->with('error', 'File backup gagal atau sedang diproses.');
        }

        $disk = Storage::disk('backups');
        if (!$disk->exists($backup->filename)) {
            return back()->with('error', 'File backup tidak ditemukan di penyimpanan.');
        }

        // Validate filepath doesn't do path traversal
        if (str_contains($backup->filename, '..') || str_contains($backup->filename, '/') || str_contains($backup->filename, '\\')) {
            return back()->with('error', 'Akses file tidak valid.');
        }

        return $disk->download($backup->filename);
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
        
        // Store uploaded file temporarily in private backups disk
        $tempPath = $file->storeAs('temp', 'restore_' . time() . '.sql', 'backups');
        $fullPath = Storage::disk('backups')->path($tempPath);

        try {
            // Execute restore
            $this->backupService->restore($fullPath);

            // Clean up temp file
            Storage::disk('backups')->delete($tempPath);

            return back()->with('success', 'Database berhasil dipulihkan dari backup.');

        } catch (Throwable $e) {
            // Clean up temp file on failure
            Storage::disk('backups')->delete($tempPath);

            Log::error('Restore database failed. Error: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            return back()->with('error', 'Gagal memulihkan database. Detail: ' . $e->getMessage());
        }
    }

    /**
     * Clean old backup files (retention: 7 days).
     */
    private function cleanOldBackups(): void
    {
        $oldBackups = Backup::where('created_at', '<', now()->subDays(7))->get();

        foreach ($oldBackups as $backup) {
            if ($backup->filename && Storage::disk('backups')->exists($backup->filename)) {
                Storage::disk('backups')->delete($backup->filename);
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
