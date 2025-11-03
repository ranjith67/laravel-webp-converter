<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WebP Quality
    |--------------------------------------------------------------------------
    |
    | The quality of the WebP image. Value between 0 and 100.
    | Higher value means better quality but larger file size.
    |
    */
    'quality' => 80,

    /*
    |--------------------------------------------------------------------------
    | Keep Original Image
    |--------------------------------------------------------------------------
    |
    | Whether to keep the original image file after conversion.
    | Useful for fallback support in older browsers.
    |
    */
    'keep_original' => true,

    /*
    |--------------------------------------------------------------------------
    | Storage Disk
    |--------------------------------------------------------------------------
    |
    | The disk where images will be stored. Must be defined in 
    | config/filesystems.php
    |
    */
    'disk' => 'public',

    /*
    |--------------------------------------------------------------------------
    | Image Sizes
    |--------------------------------------------------------------------------
    |
    | Define different image sizes to generate. Key is the size name,
    | value is the width in pixels (height will be auto-calculated).
    |
    */
    'sizes' => [
        'thumbnail' => 150,
        'medium' => 500,
        'large' => 1200,
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Extensions
    |--------------------------------------------------------------------------
    |
    | Image extensions that can be converted to WebP.
    |
    */
    'allowed_extensions' => ['jpg', 'jpeg', 'png'],
];