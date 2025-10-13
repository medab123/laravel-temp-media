# Laravel Temp Media - Complete Documentation

## Table of Contents

1. [Introduction](#introduction)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [Basic Usage](#basic-usage)
5. [API Reference](#api-reference)
6. [Advanced Features](#advanced-features)
7. [Events & Listeners](#events--listeners)
8. [Testing](#testing)
9. [Troubleshooting](#troubleshooting)
10. [Contributing](#contributing)

## Introduction

Laravel Temp Media is a comprehensive package for handling temporary media uploads in Laravel applications. It provides a secure, efficient way to manage temporary files that can be later transferred to permanent models using Spatie Media Library.

### Key Features

- **Temporary File Management**: Upload and manage temporary media files with automatic expiration
- **Secure Transfer System**: Transfer temporary media to permanent models with validation
- **Automatic Cleanup**: Built-in cleanup system for expired and processed files
- **Session Security**: Session-based ownership validation for enhanced security
- **Event System**: Comprehensive event system for custom integrations
- **Type Safety**: Full PHP 8.1+ type declarations and strict typing
- **PSR Compliance**: Adheres to PSR standards for clean, maintainable code

### Requirements

- PHP 8.1, 8.2, or 8.3
- Laravel 8.0 or higher
- Spatie Media Library 10.0 or 11.0

## Installation

### Step 1: Install the Package

```bash
composer require elamed123/laravel-temp-media
```

### Step 2: Publish Configuration and Migrations

```bash
php artisan vendor:publish --provider="Medox\LaravelTempMedia\TempMediaServiceProvider" --tag="temp-media-config"
php artisan vendor:publish --provider="Medox\LaravelTempMedia\TempMediaServiceProvider" --tag="temp-media-migrations"
```

### Step 3: Run Migrations

```bash
php artisan migrate
```

### Step 4: Configure Environment Variables

Add the following to your `.env` file:

```env
# Temporary Media Configuration
TEMP_MEDIA_TTL_HOURS=24
TEMP_MEDIA_MAX_SIZE=10485760
TEMP_MEDIA_DISK=public
TEMP_MEDIA_AUTO_CLEANUP=true
TEMP_MEDIA_DISPATCH_EVENTS=true
TEMP_MEDIA_VALIDATE_SESSION=true
TEMP_MEDIA_GENERATE_CONVERSIONS=false
```

## Configuration

The package provides extensive configuration options through the `config/temp-media.php` file:

### Basic Configuration

```php
return [
    // Default TTL in hours
    'default_ttl_hours' => env('TEMP_MEDIA_TTL_HOURS', 24),
    
    // Maximum file size in bytes (10MB default)
    'max_file_size' => env('TEMP_MEDIA_MAX_SIZE', 10 * 1024 * 1024),
    
    // Allowed MIME types
    'allowed_mime_types' => [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ],
    
    // Storage disk
    'disk' => env('TEMP_MEDIA_DISK', 'public'),
];
```

### Advanced Configuration

```php
// Auto cleanup settings
'enable_auto_cleanup' => env('TEMP_MEDIA_AUTO_CLEANUP', true),
'cleanup_schedule' => [
    'frequency' => env('TEMP_MEDIA_CLEANUP_FREQUENCY', 'hourly'),
    'without_overlapping' => env('TEMP_MEDIA_CLEANUP_NO_OVERLAP', true),
    'run_in_background' => env('TEMP_MEDIA_CLEANUP_BACKGROUND', true),
],

// Route configuration
'routes' => [
    'prefix' => 'api/v1/temp-media',
    'middleware' => ['api'],
    'name_prefix' => 'temp-media.',
],

// Security settings
'validate_session' => env('TEMP_MEDIA_VALIDATE_SESSION', true),
'rate_limiting' => [
    'enabled' => env('TEMP_MEDIA_RATE_LIMIT', true),
    'max_attempts' => env('TEMP_MEDIA_RATE_LIMIT_ATTEMPTS', 60),
    'decay_minutes' => env('TEMP_MEDIA_RATE_LIMIT_DECAY', 1),
],
```

## Basic Usage

### Uploading Temporary Media

#### Using the Service

```php
use Medox\LaravelTempMedia\Contracts\TempMediaServiceInterface;
use Illuminate\Http\UploadedFile;

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

#### Using the API Endpoint

```javascript
// Frontend JavaScript
const formData = new FormData();
formData.append('file', fileInput.files[0]);
formData.append('session_id', 'your-session-id');

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

### Transferring to Permanent Models

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
        
        $result = $this->transferTempMedia($transferDto, $collectionName);
        
        return $result;
    }
}
```

#### Using the Service Directly

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

### Validating Temporary Media

```php
use Medox\LaravelTempMedia\Contracts\TempMediaServiceInterface;

class MediaController extends Controller
{
    public function validate(Request $request, TempMediaServiceInterface $tempMediaService)
    {
        $ids = $request->input('ids', []);
        
        try {
            $validMedia = $tempMediaService->validateTempMediaIds($ids);
            
            return response()->json([
                'success' => true,
                'valid_ids' => array_column($validMedia, 'id'),
                'count' => count($validMedia)
            ]);
        } catch (InvalidFileException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
```

## API Reference

### TempMediaServiceInterface

#### uploadTempMedia(UploadedFile $file, ?string $sessionId = null, ?int $ttlHours = null): TempMediaUploadDTO

Uploads a temporary media file and returns upload information.

**Parameters:**
- `$file`: The uploaded file
- `$sessionId`: Optional session ID for ownership validation
- `$ttlHours`: Optional TTL override in hours

**Returns:** `TempMediaUploadDTO` with upload details

#### getTempMedia(string $id): ?TempMedia

Retrieves active temporary media by ID.

**Parameters:**
- `$id`: The temporary media ID

**Returns:** `TempMedia` model or null if not found/expired

#### validateTempMediaIds(array $ids): array

Validates an array of temporary media IDs.

**Parameters:**
- `$ids`: Array of temporary media IDs

**Returns:** Array of valid TempMedia models

**Throws:** `InvalidFileException` if any IDs are invalid

#### deleteTempMedia(string $id): bool

Deletes temporary media by ID.

**Parameters:**
- `$id`: The temporary media ID

**Returns:** Boolean indicating success

#### markAsProcessed(array $ids): void

Marks temporary media as processed.

**Parameters:**
- `$ids`: Array of temporary media IDs to mark as processed

### MediaTransferServiceInterface

#### transferTempMediaToModel(HasMedia $model, TempMediaTransferDTO $tempMediaTransferDTO, string $collectionName = 'default', array $customProperty = []): MediaTransferDTO

Transfers temporary media to a permanent model.

**Parameters:**
- `$model`: The target model implementing HasMedia
- `$tempMediaTransferDTO`: Transfer data containing media items
- `$collectionName`: Target media collection name
- `$customProperty`: Custom properties for media items

**Returns:** `MediaTransferDTO` with transfer results

#### cleanupProcessedTempMedia(): int

Cleans up processed temporary media files.

**Returns:** Number of files cleaned up

#### validateOwnership(array $tempMediaIds, ?string $sessionId = null, ?string $userId = null): bool

Validates ownership of temporary media.

**Parameters:**
- `$tempMediaIds`: Array of temporary media IDs
- `$sessionId`: Optional session ID for validation
- `$userId`: Optional user ID for validation

**Returns:** Boolean indicating all media belongs to the specified owner

#### getTransferStats(): array

Gets transfer statistics.

**Returns:** Array with statistics about temporary media

### API Endpoints

#### POST /api/v1/temp-media

Upload temporary media.

**Request:**
```json
{
    "file": "multipart/form-data file",
    "session_id": "optional-session-id"
}
```

**Response:**
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

#### GET /api/v1/temp-media/{id}

Get temporary media details.

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "url": "https://example.com/storage/temp_files/...",
        "thumb_url": "https://example.com/storage/temp_files/conversions/...",
        "original_name": "image.jpg",
        "size": 1024000,
        "mime_type": "image/jpeg",
        "expires_at": "2024-01-02T12:00:00.000000Z"
    }
}
```

#### DELETE /api/v1/temp-media/{id}

Delete temporary media.

**Response:**
```json
{
    "success": true,
    "message": "Temp media deleted successfully"
}
```

#### POST /api/v1/temp-media/validate

Validate temporary media IDs.

**Request:**
```json
{
    "ids": [1, 2, 3]
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "valid_ids": [1, 2],
        "count": 2
    }
}
```

## Advanced Features

### Custom Media Properties

When transferring media, you can add custom properties:

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

You can specify order for media items:

```php
$items = [
    new TempMediaItemDTO('temp-id-1', 1), // First position
    new TempMediaItemDTO('temp-id-2', 2), // Second position
    new TempMediaItemDTO('temp-id-3', 3), // Third position
];

$transferDto = new TempMediaTransferDTO($items);
```

### Manual Cleanup

#### Using Artisan Command

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

#### Using Services

```php
use Medox\LaravelTempMedia\Contracts\TempMediaServiceInterface;
use Medox\LaravelTempMedia\Contracts\MediaTransferServiceInterface;

// Clean up expired media
$expiredCount = app(TempMediaServiceInterface::class)->cleanupExpired();

// Clean up processed media
$processedCount = app(MediaTransferServiceInterface::class)->cleanupProcessedTempMedia();
```

### Media Conversions

Enable thumbnail generation for temporary media:

```php
// In config/temp-media.php
'generate_conversions' => true,
```

This will generate:
- `thumb`: 300x300 pixels with sharpening
- `small`: 150x150 pixels

### Rate Limiting

Configure rate limiting for uploads:

```php
// In config/temp-media.php
'rate_limiting' => [
    'enabled' => true,
    'max_attempts' => 60, // 60 uploads per minute
    'decay_minutes' => 1,
],
```

## Events & Listeners

### Available Events

#### TempMediaUploaded

Dispatched when temporary media is uploaded.

```php
use Medox\LaravelTempMedia\Events\TempMediaUploaded;

Event::listen(TempMediaUploaded::class, function (TempMediaUploaded $event) {
    $tempMedia = $event->tempMedia;
    $uploadDto = $event->uploadDto;
    
    // Log upload activity
    Log::info('Temporary media uploaded', [
        'id' => $tempMedia->id,
        'original_name' => $tempMedia->original_name,
        'size' => $tempMedia->size
    ]);
});
```

#### MediaTransferred

Dispatched when media is transferred to a permanent model.

```php
use Medox\LaravelTempMedia\Events\MediaTransferred;

Event::listen(MediaTransferred::class, function (MediaTransferred $event) {
    $model = $event->targetModel;
    $transferDto = $event->transferDto;
    
    // Send notification about successful transfer
    Notification::send($model->user, new MediaTransferredNotification($transferDto));
});
```

#### TempMediaExpired

Dispatched when temporary media expires and is cleaned up.

```php
use Medox\LaravelTempMedia\Events\TempMediaExpired;

Event::listen(TempMediaExpired::class, function (TempMediaExpired $event) {
    $tempMedia = $event->tempMedia;
    
    // Log cleanup activity
    Log::info('Temporary media expired and cleaned up', [
        'id' => $tempMedia->id,
        'original_name' => $tempMedia->original_name
    ]);
});
```

### Event Listener Registration

Register listeners in your `EventServiceProvider`:

```php
use Medox\LaravelTempMedia\Events\TempMediaUploaded;
use Medox\LaravelTempMedia\Events\MediaTransferred;
use Medox\LaravelTempMedia\Events\TempMediaExpired;

protected $listen = [
    TempMediaUploaded::class => [
        LogTempMediaUpload::class,
    ],
    MediaTransferred::class => [
        NotifyMediaTransfer::class,
    ],
    TempMediaExpired::class => [
        LogTempMediaExpiration::class,
    ],
];
```

## Testing

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run with coverage
php artisan test --coverage
```

### Writing Tests

#### Feature Test Example

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

#### Unit Test Example

```php
use Medox\LaravelTempMedia\Tests\TestCase;
use Medox\LaravelTempMedia\Models\TempMedia;
use Medox\LaravelTempMedia\Database\Factories\TempMediaFactory;

class TempMediaTest extends TestCase
{
    public function test_can_determine_if_expired()
    {
        $expiredMedia = TempMediaFactory::new()->expired()->create();
        $activeMedia = TempMediaFactory::new()->create();
        
        $this->assertTrue($expiredMedia->isExpired());
        $this->assertFalse($activeMedia->isExpired());
    }
}
```

### Test Database

The package includes a factory for creating test data:

```php
use Medox\LaravelTempMedia\Database\Factories\TempMediaFactory;

// Create a basic temp media
$tempMedia = TempMediaFactory::new()->create();

// Create expired temp media
$expiredMedia = TempMediaFactory::new()->expired()->create();

// Create processed temp media
$processedMedia = TempMediaFactory::new()->processed()->create();

// Create with specific session
$sessionMedia = TempMediaFactory::new()->withSession('test-session')->create();

// Create PNG file
$pngMedia = TempMediaFactory::new()->png()->create();
```

## Troubleshooting

### Common Issues

#### File Upload Fails

**Problem:** File upload returns 400 error.

**Solutions:**
1. Check file size limits in configuration
2. Verify MIME type is allowed
3. Ensure file is valid (not corrupted)
4. Check rate limiting settings

```php
// Debug file validation
$file = $request->file('file');
dd([
    'is_valid' => $file->isValid(),
    'size' => $file->getSize(),
    'mime_type' => $file->getMimeType(),
    'max_size' => config('temp-media.max_file_size'),
    'allowed_types' => config('temp-media.allowed_mime_types')
]);
```

#### Transfer Fails

**Problem:** Media transfer fails with validation error.

**Solutions:**
1. Ensure temporary media IDs are valid and not expired
2. Check that target model implements HasMedia interface
3. Verify session ownership if session validation is enabled

```php
// Debug transfer validation
$tempMediaService = app(TempMediaServiceInterface::class);
$validMedia = $tempMediaService->validateTempMediaIds($tempMediaIds);
dd($validMedia);
```

#### Cleanup Not Working

**Problem:** Expired media is not being cleaned up.

**Solutions:**
1. Check if auto cleanup is enabled
2. Verify scheduler is running
3. Run manual cleanup command
4. Check database for expired records

```bash
# Check if cleanup is scheduled
php artisan schedule:list

# Run manual cleanup
php artisan temp-media:cleanup --dry-run

# Check expired media in database
php artisan tinker
>>> TempMedia::expired()->count()
```

#### Session Validation Issues

**Problem:** Session validation fails unexpectedly.

**Solutions:**
1. Ensure session is properly configured
2. Check if session validation is enabled
3. Verify session ID is being passed correctly

```php
// Debug session validation
dd([
    'session_id' => session()->getId(),
    'validate_session' => config('temp-media.validate_session'),
    'temp_media_session' => $tempMedia->session_id
]);
```

### Debug Mode

Enable debug logging for troubleshooting:

```php
// In config/logging.php
'channels' => [
    'temp_media' => [
        'driver' => 'single',
        'path' => storage_path('logs/temp-media.log'),
        'level' => 'debug',
    ],
],

// In your code
Log::channel('temp_media')->debug('Temp media operation', $data);
```

### Performance Optimization

#### Database Optimization

```sql
-- Add additional indexes if needed
CREATE INDEX idx_temp_media_session_expires ON temp_media(session_id, expires_at);
CREATE INDEX idx_temp_media_processed_created ON temp_media(is_processed, created_at);
```

#### Storage Optimization

```php
// Use different disks for different environments
'disk' => env('TEMP_MEDIA_DISK', env('APP_ENV') === 'production' ? 's3' : 'public'),

// Configure S3 for production
'filesystems' => [
    'disks' => [
        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
        ],
    ],
],
```

## Contributing

### Development Setup

1. Fork the repository
2. Clone your fork
3. Install dependencies: `composer install`
4. Run tests: `php artisan test`
5. Make your changes
6. Add tests for new functionality
7. Ensure all tests pass
8. Submit a pull request

### Code Standards

- Follow PSR-12 coding standards
- Use strict types throughout
- Write comprehensive tests
- Document all public methods
- Follow SOLID principles

### Testing Requirements

- All new features must have tests
- Maintain 100% test coverage
- Include both unit and feature tests
- Test edge cases and error conditions

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Support

For support, please open an issue on the GitHub repository or contact the maintainer at mohammed.elabidi123@gmail.com.
