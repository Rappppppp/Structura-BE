<?php


namespace App\Services;

use Intervention\Image\Facades\Image;

class ImageOptimizationService
{
    /**
     * Compress and optimize a base64 encoded image
     *
     * @param string $base64Image Base64 encoded image string
     * @param int $quality Compression quality 1-100 (default: 70)
     * @param int $maxWidth Maximum width in pixels (default: 1280)
     * @param int $maxHeight Maximum height in pixels (default: 1280)
     * @return string Optimized base64 encoded image
     */
    public static function compressBase64(
        string $base64Image,
        int $quality = 70,
        int $maxWidth = 1280,
        int $maxHeight = 1280
    ): string {
        $original = $base64Image;

        try {
            if (empty($base64Image)) {
                return $original;
            }

            // Extract base64 payload and optional mime
            $payload = $base64Image;
            $mime = null;
            if (preg_match('/^data:(image\/[a-zA-Z0-9+.-]+);base64,(.*)$/', $base64Image, $matches)) {
                $mime = $matches[1] ?? null;
                $payload = $matches[2] ?? '';
            }

            // Clean and decode
            $payload = preg_replace('/\s+/', '', $payload);
            $decoded = base64_decode($payload, true);
            if ($decoded === false) {
                return $original;
            }

            // Use Intervention Image properly
            $image = Image::make($decoded);
            if (! $image) {
                return $original;
            }

            // Resize if necessary
            if ($image->width() > $maxWidth || $image->height() > $maxHeight) {
                $image->resize($maxWidth, $maxHeight, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }

            // Preserve PNG output when source is PNG (to keep transparency), otherwise use JPEG
            $currentMime = $image->mime() ?? $mime;
            if ($currentMime && stripos($currentMime, 'png') !== false) {
                $out = $image->encode('png');
                $optimized = base64_encode((string) $out);
                return 'data:image/png;base64,' . $optimized;
            }

            $out = $image->encode('jpeg', $quality);
            $optimized = base64_encode((string) $out);
            return 'data:image/jpeg;base64,' . $optimized;
        } catch (\Throwable $e) {
            return $original;
        }
    }

    /**
     * Get size of base64 image in bytes
     *
     * @param string $base64Image Base64 encoded image
     * @return int Size in bytes
     */
    public static function getBase64Size(string $base64Image): int
    {
        $base64 = preg_replace('#^data:image/[^;]+;base64,#', '', $base64Image);
        $base64 = preg_replace('/\s+/', '', $base64);
        $length = strlen($base64);
        if ($length === 0) {
            return 0;
        }
        $padding = 0;
        if ($length >= 2 && substr($base64, -2) === '==') {
            $padding = 2;
        } elseif ($length >= 1 && substr($base64, -1) === '=') {
            $padding = 1;
        }
        $size = (int) floor($length * 3 / 4) - $padding;
        return max(0, $size);
    }

    /**
     * Format bytes to human readable format
     *
     * @param int $bytes Size in bytes
     * @return string Formatted size
     */
    public static function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 Bytes';
        }

        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB'];
        $i = (int) floor(log($bytes) / log($k));
        $i = max(0, min($i, count($sizes) - 1));

        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }
}
