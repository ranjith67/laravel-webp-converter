<?php

namespace Ranjith\LaravelWebpConverter;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class WebPConverter
{
    /**
     * Convert an image to WebP format.
     *
     * @param UploadedFile $file
     * @param string|null $directory
     * @param string|null $filename
     * @return array
     */
    public function convert(UploadedFile $file, ?string $directory = null, ?string $filename = null): array
    {
        // Validate if extension is allowed
        if (!$this->isAllowedExtension($file)) {
            throw new \Exception('File type not allowed for WebP conversion.');
        }

        // Generate filename if not provided
        if (!$filename) {
            $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $filename = $this->sanitizeFilename($filename);
        }

        // Set directory
        $directory = $directory ?? 'images';
        
        // Convert to WebP
        $webpPath = $this->convertToWebP($file, $directory, $filename);
        
        // Store original if configured
        $originalPath = null;
        if (config('webp.keep_original', true)) {
            $originalPath = $this->storeOriginal($file, $directory, $filename);
        }

        return [
            'webp' => $webpPath,
            'original' => $originalPath,
            'sizes' => $this->generateSizes($file, $directory, $filename),
        ];
    }

    /**
     * Convert image to WebP.
     *
     * @param UploadedFile $file
     * @param string $directory
     * @param string $filename
     * @return string
     */
    protected function convertToWebP(UploadedFile $file, string $directory, string $filename): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        
        // Create image resource based on file type
        switch ($extension) {
            case 'jpeg':
            case 'jpg':
                $image = imagecreatefromjpeg($file->getRealPath());
                break;
            case 'png':
                $image = imagecreatefrompng($file->getRealPath());
                break;
            default:
                throw new \Exception('Unsupported image type.');
        }

        // Preserve transparency for PNG
        if ($extension === 'png') {
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        // Create temporary file for WebP
        $tempWebP = tempnam(sys_get_temp_dir(), 'webp_');
        imagewebp($image, $tempWebP, config('webp.quality', 80));
        imagedestroy($image);

        // Store WebP file
        $webpFilename = $filename . '.webp';
        $webpPath = $directory . '/' . $webpFilename;
        
        Storage::disk(config('webp.disk', 'public'))
            ->put($webpPath, file_get_contents($tempWebP));

        // Clean up temp file
        unlink($tempWebP);

        return $webpPath;
    }

    /**
     * Store original image.
     *
     * @param UploadedFile $file
     * @param string $directory
     * @param string $filename
     * @return string
     */
    protected function storeOriginal(UploadedFile $file, string $directory, string $filename): string
    {
        $extension = $file->getClientOriginalExtension();
        $originalFilename = $filename . '.' . $extension;
        
        return $file->storeAs(
            $directory,
            $originalFilename,
            config('webp.disk', 'public')
        );
    }

    /**
     * Generate different sizes of the image.
     *
     * @param UploadedFile $file
     * @param string $directory
     * @param string $filename
     * @return array
     */
    protected function generateSizes(UploadedFile $file, string $directory, string $filename): array
    {
        $sizes = config('webp.sizes', []);
        $generatedSizes = [];

        foreach ($sizes as $sizeName => $width) {
            $generatedSizes[$sizeName] = $this->resizeAndConvert(
                $file,
                $directory,
                $filename,
                $sizeName,
                $width
            );
        }

        return $generatedSizes;
    }

    /**
     * Resize and convert image.
     *
     * @param UploadedFile $file
     * @param string $directory
     * @param string $filename
     * @param string $sizeName
     * @param int $width
     * @return string
     */
    protected function resizeAndConvert(
        UploadedFile $file,
        string $directory,
        string $filename,
        string $sizeName,
        int $width
    ): string {
        $extension = strtolower($file->getClientOriginalExtension());
        
        // Create image resource
        switch ($extension) {
            case 'jpeg':
            case 'jpg':
                $image = imagecreatefromjpeg($file->getRealPath());
                break;
            case 'png':
                $image = imagecreatefrompng($file->getRealPath());
                break;
            default:
                throw new \Exception('Unsupported image type.');
        }

        // Get original dimensions
        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);

        // Calculate new height maintaining aspect ratio
        $height = intval($originalHeight * ($width / $originalWidth));

        // Create resized image
        $resized = imagecreatetruecolor($width, $height);
        
        // Preserve transparency for PNG
        if ($extension === 'png') {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
        }

        imagecopyresampled(
            $resized, $image,
            0, 0, 0, 0,
            $width, $height,
            $originalWidth, $originalHeight
        );

        // Create temporary WebP file
        $tempWebP = tempnam(sys_get_temp_dir(), 'webp_');
        imagewebp($resized, $tempWebP, config('webp.quality', 80));
        
        imagedestroy($image);
        imagedestroy($resized);

        // Store resized WebP
        $webpFilename = $filename . '_' . $sizeName . '.webp';
        $webpPath = $directory . '/' . $webpFilename;
        
        Storage::disk(config('webp.disk', 'public'))
            ->put($webpPath, file_get_contents($tempWebP));

        unlink($tempWebP);

        return $webpPath;
    }

    /**
     * Check if file extension is allowed.
     *
     * @param UploadedFile $file
     * @return bool
     */
    protected function isAllowedExtension(UploadedFile $file): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $allowed = config('webp.allowed_extensions', ['jpg', 'jpeg', 'png']);
        
        return in_array($extension, $allowed);
    }

    /**
     * Sanitize filename.
     *
     * @param string $filename
     * @return string
     */
    protected function sanitizeFilename(string $filename): string
    {
        // Remove special characters and replace spaces with underscores
        $filename = preg_replace('/[^A-Za-z0-9\-_]/', '', str_replace(' ', '_', $filename));
        
        // Add timestamp to make it unique
        return $filename . '_' . time();
    }
}