<?php

namespace App\Services\Accurate;

use RuntimeException;

class AccurateException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $errorCode = 'ACCURATE_ERROR',
        public readonly ?int $status = null,
        public readonly mixed $providerBody = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
