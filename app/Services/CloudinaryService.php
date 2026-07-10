<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\UploadedFile;

class CloudinaryService
{
    /**
     * Upload a file to Cloudinary and return the secure URL and public ID.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @param  string|null  $oldPublicId
     * @return array
     * @throws \Exception
     */
    public function upload(UploadedFile $file, ?string $oldPublicId = null): array
    {
        $cloudName = config('services.cloudinary.cloud_name');
        $apiKey = config('services.cloudinary.api_key');
        $apiSecret = config('services.cloudinary.api_secret');

        if (!$cloudName || !$apiKey || !$apiSecret) {
            throw new \Exception("Konfigurasi Cloudinary belum lengkap di berkas .env. Pastikan CLOUDINARY_CLOUD_NAME, CLOUDINARY_API_KEY, dan CLOUDINARY_API_SECRET sudah diisi.");
        }

        // Delete old photo if it exists
        if ($oldPublicId) {
            $this->delete($oldPublicId);
        }

        $timestamp = time();
        $params = [
            'timestamp' => $timestamp,
            'folder' => 'pos-toko/products',
            'transformation' => 'w_800,h_800,c_limit,q_auto,f_auto',
        ];

        // Sort parameters alphabetically
        ksort($params);

        // Build string to sign
        $parameterString = '';
        foreach ($params as $key => $value) {
            $parameterString .= $key . '=' . $value . '&';
        }
        $parameterString = rtrim($parameterString, '&');

        // Generate signature
        $signature = sha1($parameterString . $apiSecret);

        // Use Http::attach() — the proper Laravel way to send multipart file uploads.
        // This avoids format inconsistencies across different Guzzle/PHP versions.
        \Log::info('Cloudinary upload diagnostic', [
            'file_valid' => $file->isValid(),
            'file_error' => $file->getError(),
            'file_error_message' => $file->getErrorMessage(),
            'real_path' => $file->getRealPath(),
            'path_exists' => file_exists($file->getRealPath()),
            'path_readable' => is_readable($file->getRealPath()),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'cloud_name' => $cloudName,
        ]);

        $response = Http::timeout(60)
            ->attach(
                'file',
                fopen($file->getRealPath(), 'r'),
                $file->getClientOriginalName()
            )
            ->post(
                "https://api.cloudinary.com/v1_1/{$cloudName}/image/upload",
                array_merge($params, [
                    'api_key' => $apiKey,
                    'signature' => $signature,
                ])
            );

        \Log::info('Cloudinary upload response', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        if ($response->failed()) {
            $errorMsg = $response->json('error.message')
                ?? $response->body()
                ?? 'Terjadi kesalahan tidak dikenal.';
            throw new \Exception("Gagal mengunggah foto ke Cloudinary: " . $errorMsg);
        }

        return [
            'secure_url' => $response->json('secure_url'),
            'public_id' => $response->json('public_id'),
        ];
    }

    /**
     * Delete an image from Cloudinary by its public ID.
     *
     * @param  string  $publicId
     * @return void
     */
    public function delete(string $publicId): void
    {
        $cloudName = config('services.cloudinary.cloud_name');
        $apiKey = config('services.cloudinary.api_key');
        $apiSecret = config('services.cloudinary.api_secret');

        if (!$cloudName || !$apiKey || !$apiSecret) {
            return;
        }

        $timestamp = time();
        $params = [
            'public_id' => $publicId,
            'timestamp' => $timestamp,
        ];

        ksort($params);

        $parameterString = '';
        foreach ($params as $key => $value) {
            $parameterString .= $key . '=' . $value . '&';
        }
        $parameterString = rtrim($parameterString, '&');

        $signature = sha1($parameterString . $apiSecret);

        try {
            // Use asForm() for non-file POST — more reliable than asMultipart for simple key-value data
            $response = Http::asForm()->post(
                "https://api.cloudinary.com/v1_1/{$cloudName}/image/destroy",
                [
                    'public_id' => $publicId,
                    'api_key' => $apiKey,
                    'timestamp' => $timestamp,
                    'signature' => $signature,
                ]
            );

            if ($response->failed()) {
                \Log::warning("Gagal menghapus foto dari Cloudinary (public_id: {$publicId}): " . ($response->json('error.message') ?? 'Terjadi kesalahan tidak dikenal.'));
            }
        } catch (\Exception $e) {
            \Log::warning("Exception saat menghapus foto dari Cloudinary: " . $e->getMessage());
        }
    }
}
