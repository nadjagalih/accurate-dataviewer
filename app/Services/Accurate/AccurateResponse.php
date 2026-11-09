<?php

namespace App\Services\Accurate;

final readonly class AccurateResponse
{
    public function __construct(
        public mixed $body,
        public int $status,
        public int $durationMs,
        public int $sizeBytes,
        public bool $providerSuccess,
        public string $source = 'live',
    ) {}
}
