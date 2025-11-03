<?php

namespace Ranjith\LaravelWebpConverter\Traits;

use Illuminate\Http\UploadedFile;
use Ranjith\LaravelWebpConverter\WebPConverter;

trait HasWebPImages
{
    /**
     * Automatically convert images to WebP when setting attributes.
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function setAttribute($key, $value)
    {
        // Check if this attribute should be converted to WebP
        if ($this->shouldConvertToWebP($key, $value)) {
            // Convert Livewire temporary file to UploadedFile if needed
            $uploadedFile = $this->convertToUploadedFile($value);
            
            $converter = app('webp-converter');
            $result = $converter->convert($uploadedFile, $this->getWebPDirectory($key));
            
            // Store the WebP path
            $value = $result['webp'];
            
            // Optionally store original and sizes in separate columns
            if (property_exists($this, 'webpSizeColumns') && isset($this->webpSizeColumns[$key])) {
                foreach ($result['sizes'] as $sizeName => $path) {
                    if (isset($this->webpSizeColumns[$key][$sizeName])) {
                        parent::setAttribute($this->webpSizeColumns[$key][$sizeName], $path);
                    }
                }
            }
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * Check if attribute should be converted to WebP.
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    protected function shouldConvertToWebP(string $key, $value): bool
    {
        // Check if attribute is in the webpImages array
        if (!property_exists($this, 'webpImages') || !in_array($key, $this->webpImages)) {
            return false;
        }

        // Must be an UploadedFile
        if ($value instanceof UploadedFile) {
            return true;
        }

        // Check for Livewire temporary file (Filament compatibility)
        if (is_string($value) && $this->isLivewireTemporaryFile($value)) {
            return true;
        }

        return false;
    }

    /**
     * Check if the value is a Livewire temporary file.
     *
     * @param mixed $value
     * @return bool
     */
    protected function isLivewireTemporaryFile($value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // Check for Livewire temporary file patterns
        return str_starts_with($value, 'livewire-tmp/') || 
               (class_exists(\Livewire\Features\SupportFileUploads\TemporaryUploadedFile::class) && 
                $value instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile);
    }

    /**
     * Convert Livewire temporary file to UploadedFile.
     *
     * @param mixed $value
     * @return UploadedFile
     */
    protected function convertToUploadedFile($value): UploadedFile
    {
        // Already an UploadedFile
        if ($value instanceof UploadedFile) {
            return $value;
        }

        // Handle Livewire TemporaryUploadedFile
        if (class_exists(\Livewire\Features\SupportFileUploads\TemporaryUploadedFile::class) && 
            $value instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
            // Get the real path of the temporary file
            $tempPath = $value->getRealPath();
            $originalName = $value->getClientOriginalName();
            $mimeType = $value->getMimeType();
            
            return new UploadedFile(
                $tempPath,
                $originalName,
                $mimeType,
                null,
                true // Test mode - don't validate
            );
        }

        // Handle string path (Livewire temporary file path)
        if (is_string($value) && str_starts_with($value, 'livewire-tmp/')) {
            $disk = config('filesystems.default');
            $storage = \Storage::disk($disk);
            
            if ($storage->exists($value)) {
                $tempPath = $storage->path($value);
                $originalName = basename($value);
                $mimeType = $storage->mimeType($value);
                
                return new UploadedFile(
                    $tempPath,
                    $originalName,
                    $mimeType,
                    null,
                    true
                );
            }
        }

        // Fallback - throw exception if we can't convert
        throw new \Exception('Unable to convert value to UploadedFile instance.');
    }

    /**
     * Get directory for storing WebP images.
     *
     * @param string $key
     * @return string
     */
    protected function getWebPDirectory(string $key): string
    {
        // Check if custom directories are defined
        if (property_exists($this, 'webpDirectories') && isset($this->webpDirectories[$key])) {
            return $this->webpDirectories[$key];
        }

        // Default directory based on table name and attribute
        return $this->getTable() . '/' . $key;
    }

    /**
     * Get WebP image URL.
     *
     * @param string $attribute
     * @param string|null $size
     * @return string|null
     */
    public function getWebPUrl(string $attribute, ?string $size = null): ?string
    {
        $path = $size && property_exists($this, 'webpSizeColumns') 
            && isset($this->webpSizeColumns[$attribute][$size])
            ? $this->{$this->webpSizeColumns[$attribute][$size]}
            : $this->{$attribute};

        if (!$path) {
            return null;
        }

        return \Storage::disk(config('webp.disk', 'public'))->url($path);
    }
}