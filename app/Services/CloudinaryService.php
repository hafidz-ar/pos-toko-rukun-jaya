<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\UploadedFile;

class CloudinaryService
{
    /**
     * Upload a file to Cloudinary and return the secure URL.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @return string
     * @throws \Exception
     */
    public function upload(UploadedFile $file): string
    {
        $cloudName = config('services.cloudinary.cloud_name');
        $apiKey = config('services.cloudinary.api_key');
        $apiSecret = config('services.cloudinary.api_secret');

        if (!$cloudName || !$apiKey || !$apiSecret) {
            // Fallback: If not configured, store locally or throw an error
            throw new \Exception("Konfigurasi Cloudinary belum lengkap di berkas .env. Pastikan CLOUDINARY_CLOUD_NAME, CLOUDINARY_API_KEY, dan CLOUDINARY_API_SECRET sudah diisi.");
        }

        $timestamp = time();
        $params = [
            'timestamp' => $timestamp,
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

        // Send multipart POST request to Cloudinary API
        $response = Http::asMultipart()->post(
            "https://api.cloudinary.com/v1_1/{$cloudName}/image/upload",
            [
                'file' => fopen($file->getRealPath(), 'r'),
                'api_key' => $apiKey,
                'timestamp' => $timestamp,
                'signature' => $signature,
            ]
        );

        if ($response->failed()) {
            $errorMsg = $response->json('error.message') ?? 'Terjadi kesalahan tidak dikenal.';
            throw new \Exception("Gagal mengunggah foto ke Cloudinary: " . $errorMsg);
        }

        return $response->json('secure_url');
    }
}
