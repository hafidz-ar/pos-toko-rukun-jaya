<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Throwable;

class BackupDiagnose extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:diagnose';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Melakukan diagnosa kesiapan sistem backup dan restore database (Lokal & Railway)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== DIAGNOSA SISTEM BACKUP & RESTORE ===');
        $errorsCount = 0;

        // 1. Periksa ketersediaan mysqldump
        $this->info("\n1. Memeriksa mysqldump...");
        try {
            $result = Process::run(['mysqldump', '--version']);
            if ($result->successful()) {
                $this->line('[OK] mysqldump tersedia: ' . trim($result->output()));
            } else {
                $this->error('[FAIL] mysqldump gagal dieksekusi (Exit Code ' . $result->exitCode() . ')');
                $this->error('Error Output: ' . trim($result->errorOutput()));
                $errorsCount++;
            }
        } catch (Throwable $e) {
            $this->error('[FAIL] mysqldump tidak ditemukan di system PATH.');
            $this->error('Exception: ' . $e->getMessage());
            $errorsCount++;
        }

        // 2. Periksa ketersediaan mysql client
        $this->info("\n2. Memeriksa mysql client...");
        try {
            $result = Process::run(['mysql', '--version']);
            if ($result->successful()) {
                $this->line('[OK] mysql client tersedia: ' . trim($result->output()));
            } else {
                $this->error('[FAIL] mysql client gagal dieksekusi (Exit Code ' . $result->exitCode() . ')');
                $this->error('Error Output: ' . trim($result->errorOutput()));
                $errorsCount++;
            }
        } catch (Throwable $e) {
            $this->error('[FAIL] mysql client tidak ditemukan di system PATH.');
            $this->error('Exception: ' . $e->getMessage());
            $errorsCount++;
        }

        // 3. Periksa Konfigurasi Database
        $this->info("\n3. Memeriksa konfigurasi database MySQL...");
        $connection = config('database.connections.mysql');
        if (!$connection) {
            $this->error('[FAIL] Konfigurasi database.connections.mysql tidak ditemukan.');
            $errorsCount++;
        } else {
            $this->line('[OK] Konfigurasi database MySQL ditemukan.');
            $this->line('  Host: ' . ($connection['host'] ?? '127.0.0.1'));
            $this->line('  Port: ' . ($connection['port'] ?? '3306'));
            $this->line('  User: ' . ($connection['username'] ?? 'root'));
            $this->line('  Database: ' . ($connection['database'] ?? ''));
            $this->line('  Password: ' . (!empty($connection['password']) ? '****** (Sudah Dikonfigurasi)' : '(Kosong/Tidak Dikonfigurasi)'));
        }

        // 4. Uji Koneksi Database Aktif
        $this->info("\n4. Menguji koneksi database aktif...");
        try {
            DB::connection()->getPdo();
            $this->line('[OK] Berhasil terhubung ke database.');
        } catch (Throwable $e) {
            $this->error('[FAIL] Gagal terhubung ke database.');
            $this->error('Error: ' . $e->getMessage());
            $errorsCount++;
        }

        // 5. Periksa Folder Penyimpanan Backup
        $this->info("\n5. Memeriksa folder penyimpanan backups...");
        try {
            $disk = Storage::disk('backups');
            $testFilename = 'diagnose_test_' . time() . '.txt';
            
            $disk->put($testFilename, 'test write access');
            if ($disk->exists($testFilename)) {
                $this->line('[OK] Folder backups terdaftar dan dapat ditulis.');
                $disk->delete($testFilename);
            } else {
                $this->error('[FAIL] Folder backups tidak dapat ditulis.');
                $errorsCount++;
            }
        } catch (Throwable $e) {
            $this->error('[FAIL] Disk backups tidak terkonfigurasi dengan benar.');
            $this->error('Exception: ' . $e->getMessage());
            $errorsCount++;
        }

        // 6. Uji Coba Backup Terbatas (Hanya tabel backups)
        $this->info("\n6. Menguji coba backup terbatas (Tabel backups saja)...");
        if ($connection) {
            $dbHost = $connection['host'] ?? '127.0.0.1';
            $dbPort = $connection['port'] ?? '3306';
            $dbUser = $connection['username'] ?? 'root';
            $dbPass = $connection['password'] ?? '';
            $dbName = $connection['database'] ?? '';

            $testSqlFile = 'diagnose_dump_test_' . time() . '.sql';
            $testSqlPath = Storage::disk('backups')->path($testSqlFile);

            $command = [
                'mysqldump',
                '--host=' . $dbHost,
                '--port=' . $dbPort,
                '--user=' . $dbUser,
                $this->getSslOption(),
            ];

            if ($dbPass !== null && $dbPass !== '') {
                $command[] = '--password=' . $dbPass;
            }

            $command[] = '--skip-lock-tables';
            $command[] = $dbName;
            $command[] = 'backups'; // Hanya backup tabel backups

            $fileHandle = fopen($testSqlPath, 'w');
            if ($fileHandle) {
                $stderr = '';
                $result = Process::run($command, function ($type, $buffer) use ($fileHandle, &$stderr) {
                    if ($type === 'out') {
                        fwrite($fileHandle, $buffer);
                    } elseif ($type === 'err') {
                        $stderr .= $buffer;
                    }
                });
                fclose($fileHandle);

                if ($result->successful() && file_exists($testSqlPath) && filesize($testSqlPath) > 50) {
                    $header = file_get_contents($testSqlPath, false, null, 0, 200);
                    if (str_contains($header, 'MySQL dump')) {
                        $this->line('[OK] Test dump berhasil dibuat dan valid (' . filesize($testSqlPath) . ' bytes).');
                    } else {
                        $this->error('[FAIL] Test dump dibuat tetapi format tidak valid.');
                        $errorsCount++;
                    }
                } else {
                    $this->error('[FAIL] Gagal menjalankan test dump (Exit Code ' . $result->exitCode() . ')');
                    $this->error('Error Output: ' . trim($stderr ?: $result->errorOutput()));
                    $errorsCount++;
                }

                // Hapus file test dump
                @unlink($testSqlPath);
            } else {
                $this->error('[FAIL] Gagal membuka handle file test dump.');
                $errorsCount++;
            }
        }

        // Kesimpulan
        $this->info("\n=== KESIMPULAN DIAGNOSA ===");
        if ($errorsCount === 0) {
            $this->info('SISTEM SIAP: Semua pengujian berhasil dilalui.');
            return 0;
        } else {
            $this->error("TERDAPAT KENDALA: Ada $errorsCount pengujian yang gagal. Periksa log di atas.");
            return 1;
        }
    }

    /**
     * Determine the correct SSL flag to use based on client version.
     */
    private function getSslOption(): string
    {
        try {
            $result = Process::run(['mysqldump', '--version']);
            if ($result->successful() && str_contains(strtolower($result->output()), 'mariadb')) {
                return '--skip-ssl';
            }
        } catch (Throwable $e) {
            // fallback
        }
        return '--ssl-mode=DISABLED';
    }
}
