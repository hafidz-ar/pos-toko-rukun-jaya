<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Backup;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Throwable;

class BackupCleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backups:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Membersihkan file backup database yang sudah lebih dari 7 hari';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai pembersihan backup lama (> 7 hari)...');

        try {
            $cutoffDate = now()->subDays(7);
            
            // Get backups created before cutoff date
            $oldBackups = Backup::where('created_at', '<', $cutoffDate)->get();
            $cleanedCount = 0;

            foreach ($oldBackups as $backup) {
                // If filename exists in backups disk, delete it
                if ($backup->filename && Storage::disk('backups')->exists($backup->filename)) {
                    Storage::disk('backups')->delete($backup->filename);
                    $this->line("Dihapus dari disk: {$backup->filename}");
                }

                // Delete database record
                $backup->delete();
                $cleanedCount++;
            }

            // Also clean up any abandoned failed/processing backups older than 24 hours
            $abandonedCount = 0;
            $abandonedBackups = Backup::whereIn('status', ['failed', 'processing'])
                ->where('created_at', '<', now()->subDay())
                ->get();

            foreach ($abandonedBackups as $backup) {
                if ($backup->filename && Storage::disk('backups')->exists($backup->filename)) {
                    Storage::disk('backups')->delete($backup->filename);
                }
                $backup->delete();
                $abandonedCount++;
            }

            $this->info("Pembersihan selesai. Berhasil menghapus {$cleanedCount} backup lama dan {$abandonedCount} metadata backup gagal.");
            Log::info("Backup cleanup completed. Cleaned {$cleanedCount} old backups and {$abandonedCount} failed records.");

            return 0;
        } catch (Throwable $e) {
            $this->error('Terjadi kesalahan saat membersihkan backup: ' . $e->getMessage());
            Log::error('Backup cleanup failed: ' . $e->getMessage(), ['exception' => $e]);
            return 1;
        }
    }
}
