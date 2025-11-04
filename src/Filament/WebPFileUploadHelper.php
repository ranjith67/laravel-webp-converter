<?php

namespace Ranjith\LaravelWebpConverter\Filament;

use Illuminate\Support\Facades\Storage;
use Ranjith\LaravelWebpConverter\WebPConverter;
use Illuminate\Support\Facades\Log;

class WebPFileUploadHelper
{
    /**
     * Convert uploaded image to WebP.
     * Use this in Filament's mutateFormDataBeforeCreate/Save methods.
     *
     * @param string|null $filePath
     * @param string|null $directory
     * @return string|null
     */
    public static function convert(?string $filePath, ?string $directory = null): ?string
    {
        if (!$filePath) {
            return null;
        }

        try {
            $disk = config('webp.disk', 'public');
            $fullPath = Storage::disk($disk)->path($filePath);
            
            if (!file_exists($fullPath)) {
                return $filePath;
            }

            // Check if it's an allowed image type
            $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            $allowedExtensions = config('webp.allowed_extensions', ['jpg', 'jpeg', 'png']);
            
            if (!in_array($extension, $allowedExtensions)) {
                return $filePath; // Not an image we can convert
            }

            // Create UploadedFile instance
            $uploadedFile = new \Illuminate\Http\UploadedFile(
                $fullPath,
                basename($filePath),
                mime_content_type($fullPath),
                null,
                true
            );
            
            // Get directory from file path if not provided
            if (!$directory) {
                $directory = dirname($filePath);
            }
            
            // Convert
            $converter = app('webp-converter');
            $result = $converter->convert($uploadedFile, $directory);
            
            // Delete the original uploaded file
            if (!config('webp.keep_original', true)) {
                Storage::disk($disk)->delete($filePath);
            }
            
            return $result['webp'];
            
        } catch (\Exception $e) {
            Log::error('WebP conversion failed: ' . $e->getMessage());
            return $filePath; // Return original on error
        }
    }

    /**
     * Convert with multiple sizes support.
     * Returns array with webp path and size paths.
     *
     * @param string|null $filePath
     * @param string|null $directory
     * @return array|null
     */
    public static function convertWithSizes(?string $filePath, ?string $directory = null): ?array
    {
        if (!$filePath) {
            return null;
        }

        try {
            $disk = config('webp.disk', 'public');
            $fullPath = Storage::disk($disk)->path($filePath);
            
            if (!file_exists($fullPath)) {
                return ['webp' => $filePath, 'sizes' => []];
            }

            // Check if it's an allowed image type
            $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            $allowedExtensions = config('webp.allowed_extensions', ['jpg', 'jpeg', 'png']);
            
            if (!in_array($extension, $allowedExtensions)) {
                return ['webp' => $filePath, 'sizes' => []];
            }

            // Create UploadedFile instance
            $uploadedFile = new \Illuminate\Http\UploadedFile(
                $fullPath,
                basename($filePath),
                mime_content_type($fullPath),
                null,
                true
            );
            
            // Get directory from file path if not provided
            if (!$directory) {
                $directory = dirname($filePath);
            }
            
            // Convert
            $converter = app('webp-converter');
            $result = $converter->convert($uploadedFile, $directory);
            
            // Delete the original uploaded file
            if (!config('webp.keep_original', true)) {
                Storage::disk($disk)->delete($filePath);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('WebP conversion failed: ' . $e->getMessage());
            return ['webp' => $filePath, 'sizes' => []];
        }
    }
}