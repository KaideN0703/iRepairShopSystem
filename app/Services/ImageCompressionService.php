<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ImageCompressionService
{
    /**
     * Compress uploaded photo and generate a cropped 300x300 thumbnail.
     * Returns array ['file_path' => '/storage/...', 'thumbnail_path' => '/storage/...'].
     */
    public function compressAndThumbnail(UploadedFile $file, string $folder = 'progress_photos'): array
    {
        $storageDir = storage_path("app/public/{$folder}");
        $thumbDir = storage_path("app/public/{$folder}/thumbnails");

        File::ensureDirectoryExists($storageDir);
        File::ensureDirectoryExists($thumbDir);

        $filename = Str::uuid() . '.jpg';
        $fullPath = "{$storageDir}/{$filename}";
        $thumbPath = "{$thumbDir}/{$filename}";

        $sourcePath = $file->getRealPath();

        // Load image using GD
        $img = $this->createGdImageFromPath($sourcePath);

        if (!$img) {
            // Fallback plain copy if GD fails on unhandled format
            $path = $file->storeAs("public/{$folder}", $filename);
            return [
                'file_path' => "/storage/{$folder}/{$filename}",
                'thumbnail_path' => "/storage/{$folder}/{$filename}",
            ];
        }

        $width = imagesx($img);
        $height = imagesy($img);

        // 1. Resize Main Image if width or height > 1600px
        $maxDimension = 1600;
        if ($width > $maxDimension || $height > $maxDimension) {
            if ($width >= $height) {
                $newWidth = $maxDimension;
                $newHeight = (int) round(($height / $width) * $maxDimension);
            } else {
                $newHeight = $maxDimension;
                $newWidth = (int) round(($width / $height) * $maxDimension);
            }
            $resizedImg = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resizedImg, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagejpeg($resizedImg, $fullPath, 85);
            imagedestroy($resizedImg);
        } else {
            imagejpeg($img, $fullPath, 85);
        }

        // 2. Generate 300x300 Square Thumbnail
        $thumbSize = 300;
        $thumbImg = imagecreatetruecolor($thumbSize, $thumbSize);

        // Square crop math
        if ($width > $height) {
            $srcX = (int) round(($width - $height) / 2);
            $srcY = 0;
            $srcSquare = $height;
        } else {
            $srcX = 0;
            $srcY = (int) round(($height - $width) / 2);
            $srcSquare = $width;
        }

        imagecopyresampled($thumbImg, $img, 0, 0, $srcX, $srcY, $thumbSize, $thumbSize, $srcSquare, $srcSquare);
        imagejpeg($thumbImg, $thumbPath, 80);

        imagedestroy($thumbImg);
        imagedestroy($img);

        return [
            'file_path' => "/storage/{$folder}/{$filename}",
            'thumbnail_path' => "/storage/{$folder}/thumbnails/{$filename}",
        ];
    }

    private function createGdImageFromPath(string $path)
    {
        $info = @getimagesize($path);
        if (!$info) return null;

        $mime = $info['mime'];
        return match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            'image/bmp' => @imagecreatefrombmp($path),
            default => null,
        };
    }
}
