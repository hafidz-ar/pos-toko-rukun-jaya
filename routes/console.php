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
Artisan::command('backup:run', function () {
    $this->info('Running daily backup...');

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
        $this->error('Backup failed!');
        return;
    }

    $backup = \App\Models\Backup::create([
        'filename' => $filename,
        'filepath' => $filepath,
        'file_size' => filesize($filepath),
        'created_at' => now(),
    ]);

    // Notify owner(s)
    $owners = \App\Models\User::where('role', 'owner')->where('is_active', true)->get();
    foreach ($owners as $owner) {
        \App\Models\Notification::create([
            'recipient_user_id' => $owner->id,
            'type' => 'backup',
            'is_anomaly' => false,
            'message' => 'Backup harian berhasil dibuat: ' . $filename . '. Klik untuk download.',
            'is_read' => false,
            'created_at' => now(),
        ]);

        if ($owner->telegram_chat_id) {
            try {
                app(\App\Services\TelegramService::class)->sendMessage(
                    $owner->telegram_chat_id,
                    '📦 Backup harian berhasil: ' . $filename
                );
            } catch (\Throwable $e) {
                // fail-silent
            }
        }
    }

    // Clean old backups (>7 days retention)
    $oldBackups = \App\Models\Backup::where('created_at', '<', now()->subDays(7))->get();
    foreach ($oldBackups as $old) {
        if (file_exists($old->filepath)) {
            @unlink($old->filepath);
        }
        $old->delete();
    }

    $this->info("Backup completed: {$filename}");
})->purpose('Create daily database backup');

Schedule::command('backup:run')->dailyAt('02:00');
