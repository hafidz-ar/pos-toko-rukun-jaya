<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Backup Schedule
|--------------------------------------------------------------------------
| Daily automated database backup (PRD 3.8).
| Runs at 02:00 AM every day.
*/
Artisan::command('backup:run', function (\App\Services\DatabaseBackupService $backupService) {
    $this->info('Running daily backup...');

    // 1. Create initial metadata in processing state
    $backup = \App\Models\Backup::create([
        'filename' => 'backup_pending_' . date('Y-m-d_His') . '.sql',
        'filepath' => '',
        'file_size' => 0,
        'status' => 'processing',
        'created_at' => now(),
    ]);

    try {
        $metadata = $backupService->backup();

        $backup->update([
            'filename' => $metadata['filename'],
            'filepath' => $metadata['filepath'],
            'file_size' => $metadata['file_size'],
            'status' => 'completed',
        ]);

        $this->info("Backup completed: {$backup->filename}");

        // Notify owners
        $owners = \App\Models\User::where('role', 'owner')->where('is_active', true)->get();
        foreach ($owners as $owner) {
            \App\Models\Notification::create([
                'recipient_user_id' => $owner->id,
                'type' => 'backup',
                'is_anomaly' => false,
                'message' => 'Backup harian berhasil dibuat: ' . $backup->filename . '.',
                'is_read' => false,
                'created_at' => now(),
            ]);

            if ($owner->telegram_chat_id) {
                try {
                    app(\App\Services\TelegramService::class)->sendMessage(
                        $owner->telegram_chat_id,
                        '📦 Backup harian berhasil: ' . $backup->filename
                    );
                } catch (\Throwable $e) {
                    // fail-silent
                }
            }
        }
    } catch (\Throwable $e) {
        $backup->update([
            'status' => 'failed',
            'error_message' => $e->getMessage(),
        ]);

        $this->error('Backup failed: ' . $e->getMessage());
        \Log::error('Daily scheduled backup failed. ID: ' . $backup->id . ' | Error: ' . $e->getMessage());
    }
})->purpose('Create daily database backup');

Schedule::command('backup:run')->dailyAt('02:00');
Schedule::command('backups:cleanup')->daily();
