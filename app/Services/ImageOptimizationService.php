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
        try {
            // Handle data URI format
            if (strpos($base64Image, 'data:image') === 0) {
                $base64Image = explode(',', $base64Image)[1] ?? $base64Image;
            }

            // Decode and create image
            $image = Image::make(base64_decode($base64Image));

            // Resize if necessary
            if ($image->width() > $maxWidth || $image->height() > $maxHeight) {
                $image->resize($maxWidth, $maxHeight, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }

            // Encode back to base64
            $encodedImage = base64_encode($image->encode('jpeg', $quality));
            return 'data:image/jpeg;base64,' . $encodedImage;
        } catch (\Exception $e) {
            // If compression fails, return original
            return $base64Image;
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
        $base64 = explode(',', $base64Image)[1] ?? $base64Image;
        return (int) (strlen(rtrim($base64, '=')) * 3 / 4);
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
        $i = (int) floor(log($bytes, $k));

        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }
}
