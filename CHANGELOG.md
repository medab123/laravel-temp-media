# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-01-01

### Added

#### Core Features
- **Temporary Media Upload System**: Complete system for handling temporary media uploads with automatic expiration
- **Media Transfer Service**: Transfer temporary media to permanent models using Spatie Media Library
- **Automatic Cleanup**: Built-in cleanup system for expired and processed temporary media
- **Session-based Security**: Session validation to ensure users can only access their own temporary media

#### Models & Database
- **TempMedia Model**: Eloquent model with soft deletes, media collections, and expiration handling
- **Database Migration**: Complete migration for `temp_media` table with proper indexing
- **Model Factory**: Comprehensive factory for testing with various states (expired, processed, different file types)

#### Services & Contracts
- **TempMediaService**: Core service for uploading, validating, and managing temporary media
- **MediaTransferService**: Service for transferring temporary media to permanent models
- **Service Interfaces**: Proper contract-based architecture with dependency injection

#### HTTP Layer
- **TempMediaController**: RESTful API controller with upload, show, destroy, and validate endpoints
- **Form Requests**: Validation classes for upload and validation requests
- **API Routes**: Auto-discoverable API routes with configurable prefix and middleware

#### DTOs (Data Transfer Objects)
- **TempMediaUploadDTO**: Response DTO for upload operations
- **MediaTransferDTO**: Response DTO for transfer operations with success/failure tracking
- **TempMediaTransferDTO**: Input DTO for transfer operations
- **TempMediaItemDTO**: Individual media item DTO for transfer operations

#### Events & Notifications
- **TempMediaUploaded**: Event dispatched when media is uploaded
- **MediaTransferred**: Event dispatched when media is transferred to permanent model
- **TempMediaExpired**: Event dispatched when media expires and is cleaned up

#### Commands & Scheduling
- **CleanupTempMediaCommand**: Artisan command for manual cleanup with various options
- **Automatic Scheduling**: Hourly cleanup task with configurable frequency
- **Dry Run Support**: Test cleanup operations without actually deleting files

#### Configuration
- **Comprehensive Config**: Full configuration file with all customizable options
- **Environment Variables**: Support for all configuration via environment variables
- **Rate Limiting**: Built-in rate limiting for upload endpoints
- **File Validation**: Configurable file size limits and MIME type restrictions

#### Traits & Helpers
- **HandlesTempMedia Trait**: Convenient trait for models to easily transfer temporary media
- **Media URL Helpers**: Helper methods for extracting URLs from transfer results

#### Testing
- **Feature Tests**: Complete test suite for upload and transfer functionality
- **Unit Tests**: Unit tests for models and services
- **Test Case Base**: Proper test case setup with database transactions

#### Security Features
- **File Validation**: Comprehensive file validation (size, type, validity)
- **Session Validation**: Optional session-based ownership validation
- **Ownership Validation**: Methods to validate media ownership before transfer
- **Exception Handling**: Proper exception hierarchy with specific error types

#### Performance & Optimization
- **Database Indexing**: Proper indexes on frequently queried columns
- **Soft Deletes**: Soft delete support for audit trails
- **Background Processing**: Optional background cleanup processing
- **Media Conversions**: Optional thumbnail generation for temporary media

#### Developer Experience
- **Type Safety**: Full PHP 8.1+ type declarations and strict types
- **PSR Compliance**: Adherence to PSR-1, PSR-2, PSR-4, and PSR-12 standards
- **SOLID Principles**: Clean architecture with proper separation of concerns
- **Dependency Injection**: Full dependency injection support
- **Auto Discovery**: Automatic service provider and route registration

#### Integration
- **Spatie Media Library**: Full integration with Spatie Media Library v10/v11
- **Laravel Framework**: Compatible with Laravel 8+ (PHP 8.1+ requirement)
- **Composer Support**: Proper Composer package configuration
- **Service Provider**: Auto-registration with Laravel's service container

### Technical Specifications

#### Requirements
- PHP 8.1, 8.2, or 8.3
- Laravel 8.0 or higher
- Spatie Media Library 10.0 or 11.0

#### Architecture
- **Namespace**: `Medox\LaravelTempMedia`
- **Package Name**: `elamed123/laravel-temp-media`
- **License**: MIT
- **Stability**: Stable

#### API Endpoints
- `POST /api/v1/temp-media` - Upload temporary media
- `GET /api/v1/temp-media/{id}` - Get temporary media details
- `DELETE /api/v1/temp-media/{id}` - Delete temporary media
- `POST /api/v1/temp-media/validate` - Validate temporary media IDs

#### Configuration Options
- Default TTL: 24 hours
- Max file size: 10MB
- Allowed MIME types: image/jpeg, image/png, image/webp, image/gif
- Auto cleanup: Enabled (hourly)
- Event dispatching: Enabled
- Session validation: Enabled
- Media conversions: Disabled by default

### Breaking Changes
- None (initial release)

### Deprecations
- None (initial release)

### Security
- All file uploads are validated for type and size
- Session-based ownership validation
- Proper exception handling for security-related errors
- Rate limiting support for upload endpoints

### Performance
- Optimized database queries with proper indexing
- Background cleanup processing
- Optional media conversion generation
- Efficient file handling with Spatie Media Library

### Documentation
- Comprehensive inline documentation
- Type hints throughout the codebase
- Clear method and class documentation
- Example usage in tests and documentation
