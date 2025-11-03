# Laravel WebP Converter

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

A Laravel package that automatically converts uploaded images (JPEG, PNG) to the modern WebP format, providing better compression and faster loading times while maintaining image quality.

## Features

- 🚀 **Automatic conversion** of uploaded images to WebP format
- 🎨 **Preserves transparency** for PNG images
- 📏 **Generate multiple sizes** of images automatically
- 🔧 **Highly configurable** quality, storage disk, and sizes
- 🎯 **Easy integration** with Eloquent models using traits
- 💾 **Optional original file retention** for fallback support
- ⚡ **Lightweight** and uses native PHP GD library

## Requirements

- PHP 8.1, 8.2, or 8.3
- Laravel 11.x or 12.x
- PHP GD Extension (with WebP support)

## Installation

Install the package via Composer:

```bash
composer require ranjith/laravel-webp-converter
```

The service provider will be automatically registered.

### Publish Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=webp-config
```

This will create a `config/webp.php` file where you can customize the package behavior.

## Configuration

The `config/webp.php` file provides several configuration options:

```php
return [
    // WebP image quality (0-100, higher is better quality but larger file)
    'quality' => 80,

    // Keep the original image file after conversion
    'keep_original' => true,

    // Storage disk (must be defined in config/filesystems.php)
    'disk' => 'public',

    // Define different image sizes to generate
    'sizes' => [
        'thumbnail' => 150,
        'medium' => 500,
        'large' => 1200,
    ],

    // Image extensions that can be converted to WebP
    'allowed_extensions' => ['jpg', 'jpeg', 'png'],
];
```

## Usage

### Method 1: Using the WebPConverter Class Directly

You can use the `WebPConverter` class to convert uploaded files manually:

```php
use Ranjith\LaravelWebpConverter\WebPConverter;

class ProductController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $converter = app('webp-converter');
        $result = $converter->convert(
            $request->file('image'),
            'products' // optional directory
        );

        // $result contains:
        // - 'webp': path to the WebP image
        // - 'original': path to original image (if keep_original is true)
        // - 'sizes': array of generated size paths

        $product = Product::create([
            'name' => $request->name,
            'image' => $result['webp'],
            'image_thumbnail' => $result['sizes']['thumbnail'] ?? null,
            'image_medium' => $result['sizes']['medium'] ?? null,
        ]);

        return response()->json($product);
    }
}
```

### Method 2: Using the HasWebPImages Trait (Recommended)

The `HasWebPImages` trait provides automatic conversion when setting model attributes.

#### Step 1: Add the Trait to Your Model

```php
use Illuminate\Database\Eloquent\Model;
use Ranjith\LaravelWebpConverter\Traits\HasWebPImages;

class Product extends Model
{
    use HasWebPImages;

    protected $fillable = ['name', 'image', 'thumbnail', 'medium', 'large'];

    // Define which attributes should be converted to WebP
    protected $webpImages = ['image'];

    // Optional: Define custom directories for each attribute
    protected $webpDirectories = [
        'image' => 'products/images',
    ];

    // Optional: Map sizes to columns
    protected $webpSizeColumns = [
        'image' => [
            'thumbnail' => 'thumbnail',
            'medium' => 'medium',
            'large' => 'large',
        ],
    ];
}
```

#### Step 2: Use It in Your Controller

```php
class ProductController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // The trait automatically converts the uploaded file to WebP
        $product = Product::create([
            'name' => $request->name,
            'image' => $request->file('image'),
        ]);

        return response()->json($product);
    }
}
```

#### Step 3: Display Images in Your Views

```blade
<!-- Get the WebP image URL -->
<img src="{{ $product->getWebPUrl('image') }}" alt="{{ $product->name }}">

<!-- Get a specific size -->
<img src="{{ $product->getWebPUrl('image', 'thumbnail') }}" alt="{{ $product->name }}">

<!-- With fallback for older browsers -->
<picture>
    <source srcset="{{ $product->getWebPUrl('image') }}" type="image/webp">
    <img src="{{ Storage::url($product->original_image) }}" alt="{{ $product->name }}">
</picture>
```

## Advanced Usage

### Custom Image Sizes

Define custom sizes in your `config/webp.php`:

```php
'sizes' => [
    'thumbnail' => 150,
    'small' => 300,
    'medium' => 500,
    'large' => 1200,
    'xlarge' => 1920,
],
```

### Using Different Storage Disks

You can specify a different disk in the configuration or per model:

```php
// In config/webp.php
'disk' => 's3',

// Or use environment variables
'disk' => env('WEBP_DISK', 'public'),
```

### Adjusting WebP Quality

Higher quality means better image quality but larger file sizes:

```php
// In config/webp.php
'quality' => 90, // 0-100
```

### Processing Existing Images

You can also convert existing images programmatically:

```php
use Ranjith\LaravelWebpConverter\WebPConverter;
use Illuminate\Http\UploadedFile;

$converter = app('webp-converter');

// Assuming you have a file path
$uploadedFile = new UploadedFile(
    storage_path('app/public/old-image.jpg'),
    'old-image.jpg'
);

$result = $converter->convert($uploadedFile, 'converted');
```

## How It Works

1. **Upload**: When an image is uploaded, the package validates the file type
2. **Store**: Laravel stores the original file with a secure random name
3. **Convert**: The package converts the image to WebP format using PHP GD
4. **Resize**: If configured, multiple sizes are generated
5. **Save**: All versions are saved to your configured storage disk
6. **Clean**: Optionally, the original file is deleted if `keep_original` is false

## Database Schema Example

Here's a sample migration for using the trait with multiple sizes:

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('image')->nullable();
    $table->string('thumbnail')->nullable();
    $table->string('medium')->nullable();
    $table->string('large')->nullable();
    $table->timestamps();
});
```

## Browser Support

WebP is supported by all modern browsers:

- Chrome 23+
- Firefox 65+
- Edge 18+
- Safari 14+
- Opera 12.1+

For older browsers, use the `<picture>` element with fallback images.

## Troubleshooting

### GD Library Not Installed or WebP Support Missing

Make sure PHP GD extension is installed with WebP support:

```bash
# Ubuntu/Debian
sudo apt-get install php-gd

# Check if WebP is supported
php -r "var_dump(function_exists('imagewebp'));"
```

### Storage Disk Not Found

Ensure your storage disk is properly configured in `config/filesystems.php`:

```php
'disks' => [
    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL').'/storage',
        'visibility' => 'public',
    ],
],
```

Don't forget to create the symbolic link:

```bash
php artisan storage:link
```

## Testing

The package uses native Laravel features and can be tested in your application:

```php
public function test_image_conversion()
{
    Storage::fake('public');

    $file = UploadedFile::fake()->image('test.jpg');

    $converter = app('webp-converter');
    $result = $converter->convert($file, 'test');

    $this->assertNotNull($result['webp']);
    Storage::disk('public')->assertExists($result['webp']);
}
```

## Performance Considerations

- WebP images are typically **25-35% smaller** than JPEG at the same quality
- Conversion happens during upload, so it's a one-time cost
- Consider using queues for processing large images:

```php
ProcessImageJob::dispatch($uploadedFile, 'products');
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Credits

- [Ranjith Salian](https://github.com/ranjith67)
- [All Contributors](../../contributors)

## Support

If you encounter any issues or have questions, please [open an issue](../../issues) on GitHub.

---

Made with ❤️ for the Laravel community
