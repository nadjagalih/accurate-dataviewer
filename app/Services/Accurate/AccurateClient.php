<?php

namespace App\Services\Accurate;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

final class AccurateClient
{
    public function databases(): AccurateResponse
    {
        return $this->request(
            (string) config('accurate.endpoints.databases'),
            [],
            (string) config('accurate.api_url'),
            null,
            'accurate.databases',
        );
    }

    public function business(
        string $module,
        string $action,
        array $query = [],
        ?string $databaseId = null,
        bool $bypassCache = false,
    ): AccurateResponse {
        $path = config("accurate.endpoints.{$module}.{$action}");

        if (! is_string($path)) {
            throw new AccurateException('Operasi Accurate tidak diizinkan.', 'ACCURATE_OPERATION_NOT_ALLOWED', 404);
        }

        $session = app(AccurateSession::class)->get($databaseId);
        $cacheKey = 'accurate.response.'.hash('sha256', json_encode([
            $session['database_id'],
            $module,
            $action,
            $query,
        ], JSON_THROW_ON_ERROR));

        if (! $bypassCache && ($cached = Cache::get($cacheKey))) {
            return new AccurateResponse(
                $cached['body'],
                $cached['status'],
                $cached['durationMs'],
                $cached['sizeBytes'],
                $cached['providerSuccess'],
                'cache',
            );
        }

        try {
            $response = $this->request($path, $query, $session['host'], $session['session'], $cacheKey);
        } catch (AccurateException $exception) {
            if ($exception->status === 401) {
                app(AccurateSession::class)->forget($session['database_id']);
                $session = app(AccurateSession::class)->get($databaseId, true);
                $response = $this->request($path, $query, $session['host'], $session['session'], $cacheKey);
            } else {
                throw $exception;
            }
        }

        Cache::put($cacheKey, [
            'body' => $response->body,
            'status' => $response->status,
            'durationMs' => $response->durationMs,
            'sizeBytes' => $response->sizeBytes,
            'providerSuccess' => $response->providerSuccess,
        ], (int) config('accurate.cache_ttl'));

        return $response;
    }

    private function request(
        string $path,
        array $query,
        string $baseUrl,
        ?string $sessionId,
        string $cacheKey,
        bool $followProviderRedirect = true,
    ): AccurateResponse {
        if (! config('accurate.access_token')) {
            throw new AccurateException(
                'ACCURATE_ACCESS_TOKEN belum dikonfigurasi.',
                'ACCURATE_NOT_CONFIGURED',
                503,
            );
        }

        $startedAt = hrtime(true);

        try {
            $request = Http::baseUrl(rtrim($baseUrl, '/'))
                ->withHeaders($this->authenticationHeaders())
                ->timeout((int) config('accurate.timeout'));

            if ($sessionId !== null) {
                $request = $request->withHeaders(['X-Session-ID' => $sessionId]);
            }

            $response = $request->get($path, $query);
        } catch (ConnectionException $exception) {
            throw new AccurateException(
                'Accurate tidak dapat dihubungi.',
                'ACCURATE_CONNECTION_FAILED',
                503,
                null,
                $exception,
            );
        }

        $body = $response->json();

        if ($followProviderRedirect && $response->status() === 308) {
            $redirectHost = data_get($body, 'endpoint');

            if (is_string($redirectHost) && filter_var($redirectHost, FILTER_VALIDATE_URL)) {
                return $this->request(
                    $path,
                    $query,
                    $redirectHost,
                    $sessionId,
                    $cacheKey,
                    false,
                );
            }
        }

        $durationMs = (int) round((hrtime(true) - $startedAt) / 1_000_000);
        $sizeBytes = strlen($response->body());
        $providerSuccess = $response->successful() && data_get($body, 's', true) !== false;

        if ($response->failed() || ! $providerSuccess) {
            $providerMessage = data_get($body, 'd.0');

            throw new AccurateException(
                is_string($providerMessage) ? $providerMessage : 'Accurate menolak permintaan.',
                $response->status() === 401 ? 'ACCURATE_UNAUTHORIZED' : 'ACCURATE_REQUEST_FAILED',
                $response->status(),
                null,
            );
        }

        return new AccurateResponse($body, $response->status(), $durationMs, $sizeBytes, $providerSuccess);
    }

    /**
     * @return array<string, string>
     */
    private function authenticationHeaders(): array
    {
        $timestamp = Carbon::now(config('accurate.timezone'))->format('d/m/Y H:i:s');
        $secret = config('accurate.signature_secret');

        if (! is_string($secret) || $secret === '') {
            throw new AccurateException(
                'ACCURATE_SIGNATURE_SECRET belum dikonfigurasi.',
                'ACCURATE_SIGNATURE_NOT_CONFIGURED',
                503,
            );
        }

        return [
            'Authorization' => 'Bearer '.config('accurate.access_token'),
            'X-Api-Timestamp' => $timestamp,
            'X-Api-Signature' => base64_encode(hash_hmac('sha256', $timestamp, $secret, true)),
        ];
    }
}
