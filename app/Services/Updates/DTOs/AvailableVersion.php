<?php

namespace App\Services\Updates\DTOs;

use Illuminate\Support\Carbon;

class AvailableVersion
{
    public function __construct(
        public readonly string $identifier,
        public readonly ?string $description = null,
        public readonly ?Carbon $releasedAt = null,
        public readonly array $metadata = []
    ) {
    }
}
