<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class PhotoHelper
{
    public function downloadAndConvertToBase64(string $url): ?string
    {
        if (empty($url)) {
            return null;
        }
        $imageData = @file_get_contents($url);
        if ($imageData === false) {
            return null;
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($imageData);
        $base64 = base64_encode($imageData);

        return "data:$mimeType;base64,$base64";
    }

    /**
     * Persist a base64-encoded image to the public storage disk and return the relative file path.
     */
    public function saveBase64(string $base64): ?string
    {
        if (empty($base64)) {
            return null;
        }

        if (preg_match('/^data:image\/(\w+);base64,/', $base64, $matches)) {
            $extension = strtolower($matches[1]);
            $base64 = substr($base64, strpos($base64, ',') + 1);
        } else {
            return null;
        }

        /** @var string|false $decoded */
        $decoded = base64_decode($base64);
        if ($decoded === false) {
            return null;
        }

        $fileName = 'images/' . uniqid('', true) . '.' . $extension;
        Storage::disk('public')->put($fileName, $decoded);

        return $fileName;
    }

    /**
     * Decode base64-encoded image string and return binary and extension.
     * @return array{decoded:string, extension:string}|null
     */
    public function base64ToDecoded(string $base64): ?array
    {
        if (empty($base64)) {
            return null;
        }
        if (!preg_match('/^data:image\/(\w+);base64,/', $base64, $matches)) {
            return null;
        }
        $extension = strtolower($matches[1]);
        $data = substr($base64, strpos($base64, ',') + 1);
        /** @var string|false $decoded */
        $decoded = base64_decode($data);
        if ($decoded === false) {
            return null;
        }
        return ['decoded' => $decoded, 'extension' => $extension];
    }

    /**
     * Save already decoded image binary to storage and return relative path.
     */
    public function saveDecoded(string $binary, string $extension): ?string
    {
        if ($binary === '' || $extension === '') {
            return null;
        }
        $fileName = 'images/' . uniqid('', true) . '.' . strtolower($extension);
        Storage::disk('public')->put($fileName, $binary);
        return $fileName;
    }
}
