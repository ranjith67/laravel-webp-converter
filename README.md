# Laravel WebP Converter

Automatically convert uploaded images to WebP format in Laravel with support for multiple sizes and easy integration.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ranjith/laravel-webp-converter.svg?style=flat-square)](https://packagist.org/packages/ranjith/laravel-webp-converter)
[![Total Downloads](https://img.shields.io/packagist/dt/ranjith/laravel-webp-converter.svg?style=flat-square)](https://packagist.org/packages/ranjith/laravel-webp-converter)

## Features

- 🚀 **Automatic Conversion** - Simply assign an uploaded file to a model attribute
- 🖼️ **Multiple Sizes** - Generate thumbnails, medium, and large versions automatically
- 🔒 **Secure** - Uses Laravel's built-in secure file handling
- ⚙️ **Configurable** - Customize quality, sizes, and storage options
- 📦 **Laravel Integration** - Works seamlessly with Eloquent models
- 🎨 **Filament Compatible** - Works with Filament v3 & v4 admin panels (requires Livewire)
- 💾 **Keep Original** - Option to keep original images for fallback support

## Requirements

- PHP 8.1 or higher
- Laravel 11.0 or higher
- GD extension enabled
- Livewire 3.x (optional, required for Filament compatibility)

## Installation

You can install the package via composer:

```bash
composer require ranjith/laravel-webp-converter
```

The package will automatically register itself.

### Publish Configuration (Optional)

Publish the config file to customize settings:

```bash
php artisan vendor:publish --tag=webp-config
```

This will create a `config/webp.php` file where you can customize:

- Image quality
- Generated sizes (thumbnail, medium, large)
- Storage disk
- Whether to keep original images
- Allowed file extensions

## Usage

### Basic Usage

**1. Add the trait to your model:**

```php
use Illuminate\Database\Eloquent\Model;
use Ranjith\LaravelWebpConverter\Traits\HasWebPImages;

class Product extends Model
{
    use HasWebPImages;

    protected $fillable = ['name', 'image'];

    // Define which attributes should be converted to WebP
    protected $webpImages = ['image'];
}
```

**2. Upload images in your controller:**

```php
public function store(Request $request)
{
    $product = new Product();
    $product->name = $request->name;
    $product->image = $request->file('image'); // Automatically converts to WebP!
    $product->save();

    return redirect()->back();
}
```

That's it! The image is automatically converted to WebP format.

---

### With Multiple Sizes

**1. Add size columns to your migration:**

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('image')->nullable();
    $table->string('image_thumbnail')->nullable();
    $table->string('image_medium')->nullable();
    $table->string('image_large')->nullable();
    $table->timestamps();
});
```

**2. Configure your model:**

```php
use Illuminate\Database\Eloquent\Model;
use Ranjith\LaravelWebpConverter\Traits\HasWebPImages;

class Product extends Model
{
    use HasWebPImages;

    protected $fillable = ['name', 'image', 'image_thumbnail', 'image_medium', 'image_large'];

    // Define which attributes should be converted to WebP
    protected $webpImages = ['image'];

    // Map size names to database columns
    protected $webpSizeColumns = [
        'image' => [
            'thumbnail' => 'image_thumbnail',
            'medium' => 'image_medium',
            'large' => 'image_large',
        ],
    ];
}
```

**3. Display images in your views:**

```blade
<!-- Display main image -->
<img src="{{ $product->getWebPUrl('image') }}" alt="{{ $product->name }}">

<!-- Display thumbnail -->
<img src="{{ $product->getWebPUrl('image', 'thumbnail') }}" alt="{{ $product->name }}">

<!-- Display medium size -->
<img src="{{ $product->getWebPUrl('image', 'medium') }}" alt="{{ $product->name }}">

<!-- Display large size -->
<img src="{{ $product->getWebPUrl('image', 'large') }}" alt="{{ $product->name }}">
```

---

### Custom Directory

You can specify custom directories for different image attributes:

```php
class Product extends Model
{
    use HasWebPImages;

    protected $webpImages = ['image', 'gallery'];

    // Define custom directories for storage
    protected $webpDirectories = [
        'image' => 'products/featured',
        'gallery' => 'products/gallery',
    ];
}
```

**Note:** If using Filament with custom directories, ensure the `->directory()` in FileUpload matches the `$webpDirectories` value. See [Filament Integration](#filament-integration) section for details.

---

### Disable Size Generation

If you only want the main WebP image without additional sizes, set `sizes` to an empty array in `config/webp.php`:

```php
'sizes' => [],
```

Then use a simpler migration:

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('image')->nullable();
    $table->timestamps();
});
```

And simpler model:

```php
class Product extends Model
{
    use HasWebPImages;

    protected $fillable = ['name', 'image'];
    protected $webpImages = ['image'];
}
```

---

## Configuration

After publishing the config file, you can customize these settings in `config/webp.php`:

```php
return [
    // WebP image quality (0-100)
    'quality' => 80,

    // Keep original image file
    'keep_original' => true,

    // Storage disk (must be defined in config/filesystems.php)
    'disk' => 'public',

    // Image sizes to generate
    'sizes' => [
        'thumbnail' => 150,
        'medium' => 500,
        'large' => 1200,
    ],

    // Allowed file extensions
    'allowed_extensions' => ['jpg', 'jpeg', 'png'],
];
```

### Configuration Options

| Option               | Default    | Description                                                                  |
| -------------------- | ---------- | ---------------------------------------------------------------------------- |
| `quality`            | `80`       | WebP compression quality (0-100). Higher = better quality, larger file size. |
| `keep_original`      | `true`     | Keep the original uploaded image (JPG/PNG) for fallback support.             |
| `disk`               | `'public'` | Laravel storage disk to use (must be defined in `config/filesystems.php`).   |
| `sizes`              | `[...]`    | Array of image sizes to generate. Key = size name, Value = width in pixels.  |
| `allowed_extensions` | `[...]`    | Array of allowed image extensions that can be converted to WebP.             |

---

## Filament Integration

This package works seamlessly with [Filament v3 and v4](https://filamentphp.com/) admin panels through Livewire temporary file detection.

### Filament v3

```php
use Filament\Forms;
use Filament\Tables;

public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\TextInput::make('name')
                ->required(),

            Forms\Components\FileUpload::make('image')
                ->image()
                ->imageEditor()
                ->required()
                ->helperText('Image will be automatically converted to WebP'),
        ]);
}

public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('name'),

            Tables\Columns\ImageColumn::make('image')
                ->getStateUsing(fn ($record) => $record->getWebPUrl('image', 'thumbnail'))
                ->circular(),
        ]);
}
```

### Filament v4

Filament v4 changed the default file storage to `local` disk with `private` visibility. For WebP conversion to work properly with public URLs, explicitly set the disk:

```php
Forms\Components\FileUpload::make('image')
    ->image()
    ->imageEditor()
    ->disk('public')  // Important for v4!
    ->visibility('public')  // Important for v4!
    ->required()
    ->helperText('Image will be automatically converted to WebP');
```

**Why this matters:** Without these settings in v4, files are stored privately and `getWebPUrl()` may not generate accessible URLs.

### Custom Directory with Filament

If you specify a custom directory in Filament's FileUpload component, you must also define it in your model's `$webpDirectories` property:

**Filament Form:**

```php
Forms\Components\FileUpload::make('image')
    ->image()
    ->disk('public')
    ->directory('products/featured')  // Custom directory
    ->required();
```

**Model Configuration:**

```php
class Product extends Model
{
    use HasWebPImages;

    protected $webpImages = ['image'];

    // IMPORTANT: Must match Filament's directory() setting
    protected $webpDirectories = [
        'image' => 'products/featured',  // Same as FileUpload directory
    ];

    protected $webpSizeColumns = [
        'image' => [
            'thumbnail' => 'image_thumbnail',
            'medium' => 'image_medium',
            'large' => 'image_large',
        ],
    ];
}
```

**⚠️ Important:** The directory specified in `->directory()` and `$webpDirectories` must match, otherwise WebP files will be stored in the wrong location.

### How It Works

The package automatically detects and converts images uploaded through Filament's `FileUpload` component:

- Detects Livewire temporary files
- Converts them to standard UploadedFile instances
- Processes WebP conversion automatically
- Works with both traditional Laravel forms and Filament

**📖 For detailed Filament integration examples, see [FILAMENT_INTEGRATION.md](FILAMENT_INTEGRATION.md)**

---

## API Reference

### Trait Methods

#### `getWebPUrl(string $attribute, ?string $size = null): ?string`

Get the public URL of a WebP image.

**Parameters:**

- `$attribute` - The model attribute name (e.g., 'image')
- `$size` - Optional size name (e.g., 'thumbnail', 'medium', 'large')

**Returns:** Public URL of the image or `null` if not found

**Example:**

```php
$product->getWebPUrl('image'); // Main image
$product->getWebPUrl('image', 'thumbnail'); // Thumbnail version
```

---

### Model Properties

#### `protected $webpImages`

Array of model attributes that should be automatically converted to WebP.

```php
protected $webpImages = ['image', 'banner', 'gallery'];
```

#### `protected $webpSizeColumns`

Map size names to database columns for storing different image sizes.

```php
protected $webpSizeColumns = [
    'image' => [
        'thumbnail' => 'image_thumbnail',
        'medium' => 'image_medium',
        'large' => 'image_large',
    ],
];
```

#### `protected $webpDirectories`

Custom storage directories for different image attributes.

```php
protected $webpDirectories = [
    'image' => 'products/images',
    'banner' => 'products/banners',
];
```

---

## How It Works

1. **Upload** - User uploads JPG/PNG image through your form
2. **Store** - Laravel securely stores the original with a random filename
3. **Convert** - Package converts the stored image to WebP format
4. **Resize** - Generates configured sizes (thumbnail, medium, large)
5. **Save** - All paths are saved to your database
6. **Cleanup** - Optionally removes original if `keep_original` is false

**Filename Security:**
The package uses Laravel's built-in `store()` method, which generates secure random filenames like:

```
kJ3n5mP9xL2wQ8vR4tY7uI1oA6sD0fG.webp
```

---

## Examples

### Complete Product CRUD Example

**Migration:**

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description')->nullable();
    $table->decimal('price', 10, 2);
    $table->string('image')->nullable();
    $table->string('image_thumbnail')->nullable();
    $table->string('image_medium')->nullable();
    $table->string('image_large')->nullable();
    $table->timestamps();
});
```

**Model:**

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Ranjith\LaravelWebpConverter\Traits\HasWebPImages;

class Product extends Model
{
    use HasWebPImages;

    protected $fillable = [
        'name',
        'description',
        'price',
        'image',
        'image_thumbnail',
        'image_medium',
        'image_large',
    ];

    protected $webpImages = ['image'];

    protected $webpSizeColumns = [
        'image' => [
            'thumbnail' => 'image_thumbnail',
            'medium' => 'image_medium',
            'large' => 'image_large',
        ],
    ];
}
```

**Controller:**

```php
namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $product = new Product();
        $product->name = $request->name;
        $product->description = $request->description;
        $product->price = $request->price;
        $product->image = $request->file('image'); // Auto-converts to WebP!
        $product->save();

        return redirect()->route('products.index')
            ->with('success', 'Product created successfully!');
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $product->name = $request->name;
        $product->description = $request->description;
        $product->price = $request->price;

        if ($request->hasFile('image')) {
            $product->image = $request->file('image'); // Auto-converts!
        }

        $product->save();

        return redirect()->route('products.index')
            ->with('success', 'Product updated successfully!');
    }
}
```

**View (Blade):**

```blade
<!-- Product Grid -->
<div class="grid grid-cols-3 gap-4">
    @foreach($products as $product)
        <div class="border rounded p-4">
            <img
                src="{{ $product->getWebPUrl('image', 'thumbnail') }}"
                alt="{{ $product->name }}"
                class="w-full h-48 object-cover rounded"
            >
            <h3 class="mt-2 font-bold">{{ $product->name }}</h3>
            <p class="text-gray-600">${{ number_format($product->price, 2) }}</p>
            <a href="{{ route('products.show', $product) }}" class="text-blue-500">
                View Details
            </a>
        </div>
    @endforeach
</div>

<!-- Product Detail -->
<div class="max-w-4xl mx-auto">
    <div class="grid grid-cols-2 gap-8">
        <div>
            <img
                src="{{ $product->getWebPUrl('image', 'large') }}"
                alt="{{ $product->name }}"
                class="w-full rounded-lg shadow-lg"
            >
        </div>
        <div>
            <h1 class="text-3xl font-bold">{{ $product->name }}</h1>
            <p class="text-2xl text-green-600 mt-4">${{ number_format($product->price, 2) }}</p>
            <p class="mt-4 text-gray-700">{{ $product->description }}</p>
            <button class="mt-6 bg-blue-500 text-white px-6 py-2 rounded">
                Add to Cart
            </button>
        </div>
    </div>
</div>
```

---

## Troubleshooting

### GD Extension Not Enabled

**Error:** `ext-gd * -> it is missing from your system`

**Solution:** Enable the GD extension in your `php.ini`:

```ini
extension=gd
```

Restart your web server after making changes.

---

### Images Not Converting

**Check:**

1. Is the trait added to your model?
2. Is the attribute in the `$webpImages` array?
3. Is the uploaded file actually an UploadedFile instance?
4. Check storage permissions: `php artisan storage:link`
5. For Filament v4: Did you set `disk('public')` and `visibility('public')` on FileUpload?

---

### Storage Link Not Working

Run:

```bash
php artisan storage:link
```

This creates a symbolic link from `public/storage` to `storage/app/public`.

---

### Filament v4 Images Not Displaying

If you're using Filament v4 and images aren't displaying:

**Solution:** Explicitly set the disk and visibility in your FileUpload component:

```php
Forms\Components\FileUpload::make('image')
    ->disk('public')
    ->visibility('public')
    ->image();
```

Filament v4 defaults to `local` disk with `private` visibility, which prevents public URL access.

---

### Files Stored in Wrong Directory (Filament)

If WebP files are being stored in an unexpected location when using Filament:

**Problem:** Mismatch between Filament's `directory()` setting and model's `$webpDirectories`.

**Solution:** Ensure they match:

```php
// Filament FileUpload
Forms\Components\FileUpload::make('image')
    ->directory('products/featured');  // This...

// Model configuration
protected $webpDirectories = [
    'image' => 'products/featured',  // ...must match this!
];
```

If you don't specify `$webpDirectories`, the package uses `table_name/attribute_name` by default (e.g., `products/image`).

---

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Ranjith](https://github.com/ranjith67)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](https://github.com/ranjith67/laravel-webp-converter/blob/main/LICENSE) for more information.

## Support

If you find this package helpful, please consider:

- ⭐ Starring the repository
- 🐛 Reporting issues
- 📖 Improving documentation
- 🔀 Submitting pull requests

## Compatibility

| Package/Framework | Version       | Status                              |
| ----------------- | ------------- | ----------------------------------- |
| Laravel           | 11.x, 12.x    | ✅ Fully Supported                  |
| PHP               | 8.1, 8.2, 8.3 | ✅ Fully Supported                  |
| Filament          | v3.x          | ✅ Fully Supported                  |
| Filament          | v4.x          | ✅ Supported (requires disk config) |
| Livewire          | v3.x          | ✅ Fully Supported                  |
| Traditional Forms | All versions  | ✅ Fully Supported                  |

## Links

- [Documentation](https://github.com/ranjith67/laravel-webp-converter)
- [Filament Integration Guide](FILAMENT_INTEGRATION.md)
- [Packagist](https://packagist.org/packages/ranjith/laravel-webp-converter)
- [Issues](https://github.com/ranjith67/laravel-webp-converter/issues)
