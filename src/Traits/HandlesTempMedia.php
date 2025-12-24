<?php

declare(strict_types=1);

namespace Medox\LaravelTempMedia\Traits;

use Medox\LaravelTempMedia\Contracts\MediaTransferServiceInterface;
use Medox\LaravelTempMedia\DTOs\MediaTransferDTO;
use Medox\LaravelTempMedia\DTOs\TempMediaTransferDTO;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;

trait HandlesTempMedia
{
    /**
     * Transfer temporary media to this model
     */
    public function transferTempMedia(
        TempMediaTransferDTO $tempMediaTransferDTO,
        string $collectionName = 'default',
        array $customProperty = []
    ): MediaTransferDTO {
        $transferService = app(MediaTransferServiceInterface::class);

        if (! ($this instanceof HasMedia)) {
            throw new \InvalidArgumentException('Model must implement HasMedia interface');
        }

        return $transferService->transferTempMediaToModel(
            $this,
            $tempMediaTransferDTO,
            $collectionName,
            $customProperty
        );
    }


    /**
     * Get media URLs from a transfer result
     */
    public function getMediaUrlsFromTransfer(MediaTransferDTO $transferDto): array
    {
        return array_map(
            fn (array $media) => [
                'id' => $media['id'],
                'url' => $media['url'],
                'original_name' => $media['original_name'],
                'order' => $media['order'] ?? null,
            ],
            $transferDto->transferredMedia
        );
    }

    public function updateOrTransferMedia(array $attachments, string $collection): MediaCollection
    {
        $partitioned = collect($attachments)
            ->map(fn ($image, $i) => [...$image, 'order_column' => $i + 1])
            ->partition(fn ($image) => $image['is_temporary'] ?? false);

        [$newImages, $existingImages] = $partitioned;

        $this->updateMedia($existingImages->all(), $collection);

        $this->transferTempMedia(
            TempMediaTransferDTO::fromArray($newImages->all()),
            $collection
        );

        return $this->getMedia($collection);
    }
}
