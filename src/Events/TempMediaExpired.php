<?php

declare(strict_types=1);

namespace Medox\LaravelTempMedia\Events;

use Medox\LaravelTempMedia\Models\TempMedia;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class TempMediaExpired
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly TempMedia $tempMedia
    ) {}
}
