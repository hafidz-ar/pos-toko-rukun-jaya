<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DatabaseBackupService
{
    /**
     * Create a database backup.
     *
     * @return array Backup metadata
     * @throws RuntimeException
     */
    public function backup(): array
    {
        $connection = config('database.connections.mysql');
        if (!$connection) {
            throw new RuntimeException('Koneksi database MySQL tidak dikonfigurasi.');
        }

        $dbHost = $connection['host'] ?? '127.0.0.1';
        $dbPort = $connection['port'] ?? '3306';
        $dbUser = $connection['username'] ?? 'root';
        $dbPass = $connection['password'] ?? '';
        $dbName = $connection['database'] ?? '';

        if (empty($dbName)) {
            throw new RuntimeException('Nama database MySQL kosong.');
        }

        $filename = 'backup_' . date('Y-m-d_His') . '.sql';
        $disk = Storage::disk('backups');
        
        // Ensure backups directory exists
        if (!$disk->exists('')) {
            $disk->makeDirectory('');
        }

        $filepath = $disk->path($filename);

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

        $command[] = '--single-transaction';
        $command[] = '--skip-lock-tables';
        $command[] = '--routines';
        $command[] = '--triggers';
        $command[] = '--default-character-set=utf8mb4';
        $command[] = $dbName;

        // Open file handle for writing output chunk-by-chunk (memory efficient)
        $fileHandle = fopen($filepath, 'w');
        if (!$fileHandle) {
            throw new RuntimeException("Gagal membuka file backup untuk penulisan di path: $filepath");
        }

        $stderr = '';

        // Run process
        $result = Process::timeout(300)->run($command, function ($type, $buffer) use ($fileHandle, &$stderr) {
            if ($type === 'out') {
                fwrite($fileHandle, $buffer);
            } elseif ($type === 'err') {
                $stderr .= $buffer;
            }
        });

        fclose($fileHandle);

        if (!$result->successful()) {
            @unlink($filepath);
            $cleanError = $this->sanitizeErrorMessage($stderr ?: $result->errorOutput());
            throw new RuntimeException("Proses mysqldump gagal (Exit Code {$result->exitCode()}): $cleanError");
        }

        // Validate the output file
        if (!file_exists($filepath) || filesize($filepath) < 100) {
            @unlink($filepath);
            throw new RuntimeException('File backup kosong atau gagal dibuat.');
        }

        // Check if file starts with valid SQL header
        $header = file_get_contents($filepath, false, null, 0, 200);
        if (!str_contains($header, 'MySQL dump')) {
            @unlink($filepath);
            throw new RuntimeException('Format file backup tidak valid (Header MySQL dump tidak ditemukan).');
        }

        return [
            'filename' => $filename,
            'filepath' => $filepath,
            'file_size' => filesize($filepath),
            'created_at' => now(),
        ];
    }

    /**
     * Restore database from a given SQL file.
     *
     * @param string $filepath Absolute path to the SQL backup file
     * @throws RuntimeException
     */
    public function restore(string $filepath): void
    {
        if (!file_exists($filepath)) {
            throw new RuntimeException("File backup tidak ditemukan: $filepath");
        }

        $connection = config('database.connections.mysql');
        if (!$connection) {
            throw new RuntimeException('Koneksi database MySQL tidak dikonfigurasi.');
        }

        $dbHost = $connection['host'] ?? '127.0.0.1';
        $dbPort = $connection['port'] ?? '3306';
        $dbUser = $connection['username'] ?? 'root';
        $dbPass = $connection['password'] ?? '';
        $dbName = $connection['database'] ?? '';

        if (empty($dbName)) {
            throw new RuntimeException('Nama database MySQL kosong.');
        }

        $command = [
            'mysql',
            '--host=' . $dbHost,
            '--port=' . $dbPort,
            '--user=' . $dbUser,
            $this->getSslOption(),
        ];

        if ($dbPass !== null && $dbPass !== '') {
            $command[] = '--password=' . $dbPass;
        }

        $command[] = $dbName;

        // Open the backup file as stream input (memory efficient)
        $fileHandle = fopen($filepath, 'r');
        if (!$fileHandle) {
            throw new RuntimeException("Gagal membuka file backup untuk dibaca: $filepath");
        }

        // Run process with input stream redirection
        $result = Process::timeout(300)
            ->input($fileHandle)
            ->run($command);

        fclose($fileHandle);

        if (!$result->successful()) {
            $cleanError = $this->sanitizeErrorMessage($result->errorOutput());
            throw new RuntimeException("Proses mysql restore gagal (Exit Code {$result->exitCode()}): $cleanError");
        }
    }

    /**
     * Clean sensitive information (like passwords) from error messages.
     */
    private function sanitizeErrorMessage(string $message): string
    {
        if (empty($message)) {
            return 'Terjadi kesalahan sistem internal.';
        }

        // Remove passwords in warning messages
        $message = preg_replace('/using password: (YES|NO)/i', 'using password: ***', $message);
        // Remove password parameter values if leaked in commands
        $message = preg_replace('/--password=\S+/i', '--password=***', $message);

        return trim($message);
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
        } catch (\Throwable $e) {
            // fallback
        }
        return '--ssl-mode=DISABLED';
    }
}
