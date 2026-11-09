<?php

namespace App\Services\Accurate;

use Illuminate\Support\Str;

final class ResponseEnvelope
{
    public static function success(
        AccurateResponse $response,
        string $applicationEndpoint,
        array $query = [],
    ): array {
        return [
            'data' => $response->body,
            'meta' => self::meta($response, $applicationEndpoint, $query),
        ];
    }

    public static function error(
        AccurateException $exception,
        string $applicationEndpoint,
        array $query = [],
        ?int $durationMs = null,
    ): array {
        return [
            'data' => null,
            'error' => [
                'code' => $exception->errorCode,
                'message' => $exception->getMessage(),
            ],
            'meta' => [
                'id' => 'call_'.Str::lower(Str::random(16)),
                'timestamp' => now()->toIso8601String(),
                'method' => 'GET',
                'endpoint' => $applicationEndpoint,
                'query' => $query,
                'httpStatus' => $exception->status ?? 502,
                'providerSuccess' => false,
                'durationMs' => $durationMs ?? 0,
                'sizeBytes' => 0,
                'source' => 'live',
            ],
        ];
    }

    private static function meta(
        AccurateResponse $response,
        string $applicationEndpoint,
        array $query,
    ): array {
        return [
            'id' => 'call_'.Str::lower(Str::random(16)),
            'timestamp' => now()->toIso8601String(),
            'method' => 'GET',
            'endpoint' => $applicationEndpoint,
            'query' => $query,
            'httpStatus' => $response->status,
            'providerSuccess' => $response->providerSuccess,
            'durationMs' => $response->durationMs,
            'sizeBytes' => $response->sizeBytes,
            'source' => $response->source,
        ];
    }
}
