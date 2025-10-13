# Laravel Temp Media

[![Latest Version on Packagist](https://img.shields.io/packagist/v/elamed123/laravel-temp-media.svg?style=flat-square)](https://packagist.org/packages/elamed123/laravel-temp-media)
[![Total Downloads](https://img.shields.io/packagist/dt/elamed123/laravel-temp-media.svg?style=flat-square)](https://packagist.org/packages/elamed123/laravel-temp-media)
[![License](https://img.shields.io/packagist/l/elamed123/laravel-temp-media.svg?style=flat-square)](https://packagist.org/packages/elamed123/laravel-temp-media)
[![PHP Version](https://img.shields.io/packagist/php-v/elamed123/laravel-temp-media.svg?style=flat-square)](https://packagist.org/packages/elamed123/laravel-temp-media)

A comprehensive Laravel package for handling temporary media uploads with automatic cleanup and secure transfer to permanent models using Spatie Media Library.

## ✨ Features

- 🚀 **Temporary File Management** - Upload and manage temporary media files with automatic expiration
- 🔒 **Secure Transfer System** - Transfer temporary media to permanent models with validation
- 🧹 **Automatic Cleanup** - Built-in cleanup system for expired and processed files
- 🔐 **Session Security** - Session-based ownership validation for enhanced security
- 📡 **Event System** - Comprehensive event system for custom integrations
- 🎯 **Type Safety** - Full PHP 8.1+ type declarations and strict typing
- 📏 **PSR Compliance** - Adheres to PSR standards for clean, maintainable code
- 🧪 **Comprehensive Testing** - Full test suite with factories and examples
- ⚡ **Performance Optimized** - Efficient database queries and background processing

## 📋 Requirements

- PHP 8.1, 8.2, or 8.3
- Laravel 8.0 or higher
- Spatie Media Library 10.0 or 11.0

## 🚀 Quick Start

### Installation

```bash
composer require elamed123/laravel-temp-media
```

### Publish Configuration and Migrations

```bash
php artisan vendor:publish --provider="Medox\LaravelTempMedia\TempMediaServiceProvider" --tag="temp-media-config"
php artisan vendor:publish --provider="Medox\LaravelTempMedia\TempMediaServiceProvider" --tag="temp-media-migrations"
```

### Run Migrations

```bash
php artisan migrate
```

### Configure Environment Variables

Add to your `.env` file:

```env
TEMP_MEDIA_TTL_HOURS=24
TEMP_MEDIA_MAX_SIZE=10485760
TEMP_MEDIA_DISK=public
TEMP_MEDIA_AUTO_CLEANUP=true
```

## 🎯 Basic Usage

### Upload Temporary Media

#### Using the API

```javascript
const formData = new FormData();
formData.append('file', fileInput.files[0]);

fetch('/api/v1/temp-media', {
    method: 'POST',
    body: formData,
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    }
})
.then(response => response.json())
.then(data => {
    console.log('Upload successful:', data);
});
```

#### Using the Service

```php
use Medox\LaravelTempMedia\Contracts\TempMediaServiceInterface;

class MediaController extends Controller
{
    public function upload(Request $request, TempMediaServiceInterface $tempMediaService)
    {
        $file = $request->file('file');
        $sessionId = $request->input('session_id', session()->getId());
        
        $result = $tempMediaService->uploadTempMedia($file, $sessionId);
        
        return response()->json($result->toJsonResponse());
    }
}
```

### Transfer to Permanent Model

#### Using the Trait

```php
use Medox\LaravelTempMedia\Traits\HandlesTempMedia;
use Medox\LaravelTempMedia\DTOs\TempMediaTransferDTO;
use Medox\LaravelTempMedia\DTOs\TempMediaItemDTO;
use Spatie\MediaLibrary\HasMedia;

class Post extends Model implements HasMedia
{
    use HandlesTempMedia;
    
    public function attachTempMedia(array $tempMediaIds, string $collectionName = 'images')
    {
        $items = array_map(
            fn($id) => new TempMediaItemDTO($id),
            $tempMediaIds
        );
        
        $transferDto = new TempMediaTransferDTO($items);
        
        return $this->transferTempMedia($transferDto, $collectionName);
    }
}
```

#### Using the Service

```php
use Medox\LaravelTempMedia\Contracts\MediaTransferServiceInterface;
use Medox\LaravelTempMedia\DTOs\TempMediaTransferDTO;
use Medox\LaravelTempMedia\DTOs\TempMediaItemDTO;

class PostController extends Controller
{
    public function store(Request $request, MediaTransferServiceInterface $transferService)
    {
        $post = Post::create($request->validated());
        
        $tempMediaIds = $request->input('temp_media_ids', []);
        $items = array_map(
            fn($id) => new TempMediaItemDTO($id),
            $tempMediaIds
        );
        
        $transferDto = new TempMediaTransferDTO($items);
        $result = $transferService->transferTempMediaToModel($post, $transferDto, 'images');
        
        return response()->json([
            'post' => $post,
            'media_transfer' => $result->toArray()
        ]);
    }
}
```

## 📚 API Reference

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/v1/temp-media` | Upload temporary media |
| `GET` | `/api/v1/temp-media/{id}` | Get temporary media details |
| `DELETE` | `/api/v1/temp-media/{id}` | Delete temporary media |
| `POST` | `/api/v1/temp-media/validate` | Validate temporary media IDs |

### Response Examples

#### Upload Response

```json
{
    "success": true,
    "data": {
        "id": 1,
        "url": "https://example.com/storage/temp_files/...",
        "original_name": "image.jpg",
        "mime_type": "image/jpeg",
        "size": 1024000,
        "expires_at": "2024-01-02T12:00:00.000000Z",
        "is_temporary": true
    },
    "message": "File uploaded successfully"
}
```

#### Transfer Response

```json
{
    "transferred_media": [
        {
            "id": 123,
            "temp_media_id": "1",
            "url": "https://example.com/storage/images/...",
            "collection": "images",
            "original_name": "image.jpg",
            "size": 1024000,
            "mime_type": "image/jpeg",
            "order": 1
        }
    ],
    "transferred_count": 1,
    "failed_transfers": [],
    "failed_count": 0,
    "target_model_type": "App\\Models\\Post",
    "target_model_id": "1",
    "collection_name": "images"
}
```

## ⚙️ Configuration

The package provides extensive configuration options:

```php
// config/temp-media.php
return [
    // Basic settings
    'default_ttl_hours' => 24,
    'max_file_size' => 10 * 1024 * 1024, // 10MB
    'allowed_mime_types' => [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ],
    
    // Security
    'validate_session' => true,
    'rate_limiting' => [
        'enabled' => true,
        'max_attempts' => 60,
        'decay_minutes' => 1,
    ],
    
    // Cleanup
    'enable_auto_cleanup' => true,
    'cleanup_schedule' => [
        'frequency' => 'hourly',
        'without_overlapping' => true,
        'run_in_background' => true,
    ],
    
    // Events
    'dispatch_events' => true,
    
    // Media conversions
    'generate_conversions' => false,
];
```

## 🧹 Cleanup

### Automatic Cleanup

The package automatically cleans up expired and processed media hourly. You can configure this in the config file.

### Manual Cleanup

```bash
# Clean up all expired and processed media
php artisan temp-media:cleanup

# Clean up only expired media
php artisan temp-media:cleanup --expired-only

# Clean up only processed media
php artisan temp-media:cleanup --processed-only

# Dry run to see what would be cleaned
php artisan temp-media:cleanup --dry-run
```

### Programmatic Cleanup

```php
use Medox\LaravelTempMedia\Contracts\TempMediaServiceInterface;
use Medox\LaravelTempMedia\Contracts\MediaTransferServiceInterface;

// Clean up expired media
$expiredCount = app(TempMediaServiceInterface::class)->cleanupExpired();

// Clean up processed media
$processedCount = app(MediaTransferServiceInterface::class)->cleanupProcessedTempMedia();
```

## 📡 Events

The package dispatches several events for custom integrations:

### TempMediaUploaded

```php
use Medox\LaravelTempMedia\Events\TempMediaUploaded;

Event::listen(TempMediaUploaded::class, function (TempMediaUploaded $event) {
    // Handle upload event
    Log::info('Media uploaded', ['id' => $event->tempMedia->id]);
});
```

### MediaTransferred

```php
use Medox\LaravelTempMedia\Events\MediaTransferred;

Event::listen(MediaTransferred::class, function (MediaTransferred $event) {
    // Handle transfer event
    Notification::send($event->targetModel->user, new MediaTransferredNotification());
});
```

### TempMediaExpired

```php
use Medox\LaravelTempMedia\Events\TempMediaExpired;

Event::listen(TempMediaExpired::class, function (TempMediaExpired $event) {
    // Handle expiration event
    Log::info('Media expired', ['id' => $event->tempMedia->id]);
});
```

## 🧪 Testing

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
```

### Test Example

```php
use Medox\LaravelTempMedia\Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class TempMediaUploadTest extends TestCase
{
    public function test_can_upload_temporary_media()
    {
        Storage::fake('public');
        
        $file = UploadedFile::fake()->image('test.jpg');
        
        $response = $this->postJson('/api/v1/temp-media', [
            'file' => $file
        ]);
        
        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'url',
                        'original_name',
                        'mime_type',
                        'size',
                        'expires_at',
                        'is_temporary'
                    ]
                ]);
    }
}
```

## 🔧 Advanced Features

### Custom Media Properties

```php
$customProperties = [
    'alt_text' => 'Product image',
    'category' => 'product',
    'featured' => true
];

$result = $transferService->transferTempMediaToModel(
    $model,
    $transferDto,
    'images',
    $customProperties
);
```

### Order Management

```php
$items = [
    new TempMediaItemDTO('temp-id-1', 1), // First position
    new TempMediaItemDTO('temp-id-2', 2), // Second position
    new TempMediaItemDTO('temp-id-3', 3), // Third position
];

$transferDto = new TempMediaTransferDTO($items);
```

### Media Conversions

Enable thumbnail generation:

```php
// In config/temp-media.php
'generate_conversions' => true,
```

This generates:
- `thumb`: 300x300 pixels with sharpening
- `small`: 150x150 pixels

## 🐛 Troubleshooting

### Common Issues

#### File Upload Fails
- Check file size limits in configuration
- Verify MIME type is allowed
- Ensure file is valid (not corrupted)
- Check rate limiting settings

#### Transfer Fails
- Ensure temporary media IDs are valid and not expired
- Check that target model implements HasMedia interface
- Verify session ownership if session validation is enabled

#### Cleanup Not Working
- Check if auto cleanup is enabled
- Verify scheduler is running
- Run manual cleanup command

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## 👨‍💻 Author

**Elabidi Mohammed**
- Email: mohammed.elabidi123@gmail.com
- GitHub: [@elamed123](https://github.com/elamed123)

## 🙏 Acknowledgments

- [Spatie](https://spatie.be/) for the excellent Media Library package
- [Laravel](https://laravel.com/) for the amazing framework
- All contributors who help improve this package

## 📖 Documentation

For complete documentation, please visit the [Documentation](DOCUMENTATION.md) file.

## 📝 Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

---

**Made with ❤️ for the Laravel community**
