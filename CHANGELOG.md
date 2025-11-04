# Changelog

All notable changes to `laravel-webp-converter` will be documented in this file.

## v1.0.2 - 2025-11-04

### Changed
- **Refactored Architecture**: Separated Filament integration into dedicated `WebPFileUploadHelper` class
- **Simplified Trait**: Removed all Filament/Livewire-specific code from `HasWebPImages` trait
- **Cleaner Separation**: Trait now focuses solely on traditional Laravel form uploads

### Added
- **New `WebPFileUploadHelper` Class**: Dedicated helper for Filament admin panels located at `src/Filament/WebPFileUploadHelper.php`
- **Two Static Methods**:
  - `WebPFileUploadHelper::convert()` - Simple single image conversion
  - `WebPFileUploadHelper::convertWithSizes()` - Conversion with multiple size support
- **Enhanced Documentation**: Updated README with clear examples for both Laravel forms and Filament usage

### Improved
- **Better Maintainability**: Clear separation between Laravel form handling and Filament integration
- **Easier to Understand**: Users now have two distinct, simple approaches instead of complex auto-detection
- **More Flexible**: Filament users have explicit control over when conversion happens

### Fixed
- **Filament Compatibility**: Resolved issues with Filament's file upload lifecycle by using proper hooks instead of setAttribute()
- **Code Clarity**: Removed complex detection logic in favor of explicit helper methods

### Migration from v1.0.1

**For Regular Laravel Forms**: No changes needed, continues to work exactly the same.

**For Filament Users**: Update your Create/Edit pages to use the new helper:

```php
use Ranjith\LaravelWebpConverter\Filament\WebPFileUploadHelper;

protected function mutateFormDataBeforeCreate(array $data): array
{
    if (isset($data['image'])) {
        $data['image'] = WebPFileUploadHelper::convert($data['image'], 'directory');
    }
    return $data;
}
```

## v1.0.1 - 2025-11-04

### Added
- **Filament v3 & v4 Support**: Full compatibility with Filament admin panels through Livewire temporary file detection
- **Livewire Integration**: Automatic detection and conversion of Livewire temporary uploads
- **New Methods in HasWebPImages Trait**:
  - `isLivewireTemporaryFile()` - Detects Livewire temporary file patterns
  - `convertToUploadedFile()` - Converts Livewire files to UploadedFile instances
  - `shouldConvertFilamentUpload()` - Checks if string path is a Filament upload
  - `convertFilamentUpload()` - Handles Filament file conversion

### Changed
- **Updated `shouldConvertToWebP()` method**: Now detects both standard UploadedFile and Livewire temporary files
- **Updated `setAttribute()` method**: Converts Livewire files before processing

### Fixed
- **Filament FileUpload Compatibility**: Package now properly detects and converts files uploaded through Filament's FileUpload component
- **Livewire Temporary File Handling**: Correctly processes Livewire temporary uploads instead of ignoring them

## v1.0.0 - 2025-11-03

### Added
- Initial release
- Automatic WebP conversion on file upload
- Multiple size generation (thumbnail, medium, large)
- Configurable quality and sizes
- Laravel 11+ and Laravel 12+ support
- Secure filename generation using Laravel's built-in methods
- `HasWebPImages` trait for easy model integration
- Configurable storage disk support
- Option to keep or delete original images
- `getWebPUrl()` helper method for retrieving image URLs
- Support for custom directories per attribute
- MIT License

### Features
- 🚀 Automatic conversion of JPG/PNG to WebP
- 🖼️ Multiple size generation with configurable dimensions
- 🔒 Secure random filenames (Laravel standard)
- ⚙️ Fully configurable via config file
- 📦 Seamless Eloquent integration
- 💾 Optional original image preservation

### Requirements
- PHP 8.1 or higher
- Laravel 11.0 or higher
- GD extension enabled