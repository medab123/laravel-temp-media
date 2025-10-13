<?php

declare(strict_types=1);

namespace Medox\LaravelTempMedia\Contracts;

use Medox\LaravelTempMedia\DTOs\MediaTransferDTO;
use Medox\LaravelTempMedia\DTOs\TempMediaTransferDTO;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;

interface MediaTransferServiceInterface
{
    /**
     * Transfer temporary media to a model
     */
    public function transferTempMediaToModel(
        HasMedia $model,
        TempMediaTransferDTO $tempMediaTransferDTO,
        string $collectionName = 'default'
    ): MediaTransferDTO;

    /**
     * Clean up processed temporary media
     */
    public function cleanupProcessedTempMedia(): int;

    /**
     * Validate temporary media ownership before transfer
     */
    public function validateOwnership(array $tempMediaIds, ?string $sessionId = null, ?string $userId = null): bool;

    /**
     * Get transfer statistics
     */
    public function getTransferStats(): array;
}
