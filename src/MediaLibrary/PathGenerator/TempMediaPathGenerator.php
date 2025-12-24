<?php

namespace Medox\LaravelTempMedia\MediaLibrary\PathGenerator;

use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class TempMediaPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        return 'temp-media/'.$media->model_id.'/';
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getPath($media).'conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getPath($media).'responsive/';
    }
}

