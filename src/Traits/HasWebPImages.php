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
            $converter = app('webp-converter');
            $result = $converter->convert($value, $this->getWebPDirectory($key));
            
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
        // Must be an UploadedFile
        if (!$value instanceof UploadedFile) {
            return false;
        }

        // Check if attribute is in the webpImages array
        if (property_exists($this, 'webpImages') && in_array($key, $this->webpImages)) {
            return true;
        }

        return false;
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