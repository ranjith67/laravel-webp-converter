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
     * @return array
     */
    public function convert(UploadedFile $file, ?string $directory = null): array
    {
        // Validate if extension is allowed
        if (!$this->isAllowedExtension($file)) {
            throw new \Exception('File type not allowed for WebP conversion.');
        }

        $directory = $directory ?? 'images';
        $disk = config('webp.disk', 'public');
        
        // Let Laravel store the original file with secure random name
        $originalPath = $file->store($directory, $disk);
        
        // Get the secure filename Laravel generated
        $filename = pathinfo($originalPath, PATHINFO_FILENAME);
        $storedFilePath = Storage::disk($disk)->path($originalPath);
        
        // Convert the stored file to WebP
        $webpPath = $this->convertToWebP($storedFilePath, $directory, $filename, $disk);
        
        // Generate different sizes
        $sizes = $this->generateSizes($storedFilePath, $directory, $filename, $disk);
        
        // Delete original if keep_original is false
        if (!config('webp.keep_original', true)) {
            Storage::disk($disk)->delete($originalPath);
            $originalPath = null;
        }

        return [
            'webp' => $webpPath,
            'original' => $originalPath,
            'sizes' => $sizes,
        ];
    }

    /**
     * Convert stored image to WebP.
     *
     * @param string $filePath
     * @param string $directory
     * @param string $filename
     * @param string $disk
     * @return string
     */
    protected function convertToWebP(string $filePath, string $directory, string $filename, string $disk): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        // Create image resource based on file type
        switch ($extension) {
            case 'jpeg':
            case 'jpg':
                $image = imagecreatefromjpeg($filePath);
                break;
            case 'png':
                $image = imagecreatefrompng($filePath);
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

        // Store WebP file with same filename
        $webpFilename = $filename . '.webp';
        $webpPath = $directory . '/' . $webpFilename;
        
        Storage::disk($disk)->put($webpPath, file_get_contents($tempWebP));

        // Clean up temp file
        unlink($tempWebP);

        return $webpPath;
    }

    /**
     * Generate different sizes of the image.
     *
     * @param string $filePath
     * @param string $directory
     * @param string $filename
     * @param string $disk
     * @return array
     */
    protected function generateSizes(string $filePath, string $directory, string $filename, string $disk): array
    {
        $sizes = config('webp.sizes', []);
        $generatedSizes = [];

        foreach ($sizes as $sizeName => $width) {
            $generatedSizes[$sizeName] = $this->resizeAndConvert(
                $filePath,
                $directory,
                $filename,
                $sizeName,
                $width,
                $disk
            );
        }

        return $generatedSizes;
    }

    /**
     * Resize and convert image.
     *
     * @param string $filePath
     * @param string $directory
     * @param string $filename
     * @param string $sizeName
     * @param int $width
     * @param string $disk
     * @return string
     */
    protected function resizeAndConvert(
        string $filePath,
        string $directory,
        string $filename,
        string $sizeName,
        int $width,
        string $disk
    ): string {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        // Create image resource
        switch ($extension) {
            case 'jpeg':
            case 'jpg':
                $image = imagecreatefromjpeg($filePath);
                break;
            case 'png':
                $image = imagecreatefrompng($filePath);
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
        
        Storage::disk($disk)->put($webpPath, file_get_contents($tempWebP));

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
}