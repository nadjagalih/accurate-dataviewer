<?php

namespace App\Services\Accurate;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

final class AccurateSession
{
    public function get(?string $databaseId = null, bool $refresh = false): array
    {
        if ($this->usesDatabaseApiToken()) {
            return [
                'host' => rtrim((string) config('accurate.api_token_url'), '/'),
                'session' => null,
                'database_id' => 'api-token',
            ];
        }

        $databaseId ??= (string) config('accurate.default_database_id');

        if ($databaseId === '') {
            throw new AccurateException(
                'Pilih database Accurate terlebih dahulu.',
                'ACCURATE_DATABASE_REQUIRED',
                422,
            );
        }

        $key = $this->cacheKey($databaseId);

        if (! $refresh && ($session = Cache::get($key))) {
            return $session;
        }

        try {
            return Cache::lock($key.':lock', 15)->block(10, function () use ($key, $databaseId, $refresh) {
                if (! $refresh && ($session = Cache::get($key))) {
                    return $session;
                }

                $response = Http::baseUrl((string) config('accurate.api_url'))
                    ->withHeaders($this->authenticationHeaders())
                    ->asForm()
                    ->timeout((int) config('accurate.timeout'))
                    ->post((string) config('accurate.endpoints.open_database'), [
                        'id' => $databaseId,
                    ]);

                if ($response->failed()) {
                    throw new AccurateException(
                        'Accurate tidak dapat membuka database yang dipilih.',
                        'ACCURATE_OPEN_DATABASE_FAILED',
                        $response->status(),
                        null,
                    );
                }

                $body = $response->json();
                $host = data_get($body, 'host') ?? data_get($body, 'd.host');
                $sessionId = data_get($body, 'session') ?? data_get($body, 'd.session');

                if (! is_string($host) || ! is_string($sessionId) || $host === '' || $sessionId === '') {
                    throw new AccurateException(
                        'Respons pembukaan database Accurate tidak lengkap.',
                        'ACCURATE_INVALID_SESSION_RESPONSE',
                        $response->status(),
                        null,
                    );
                }

                $session = [
                    'host' => rtrim($host, '/'),
                    'session' => $sessionId,
                    'database_id' => $databaseId,
                ];

                Cache::put($key, $session, (int) config('accurate.session_ttl'));

                return $session;
            });
        } catch (LockTimeoutException $exception) {
            throw new AccurateException(
                'Sesi Accurate sedang diperbarui. Silakan coba lagi.',
                'ACCURATE_SESSION_LOCK_TIMEOUT',
                503,
                null,
                $exception,
            );
        }
    }

    public function forget(string $databaseId): void
    {
        Cache::forget($this->cacheKey($databaseId));
    }

    private function cacheKey(string $databaseId): string
    {
        return 'accurate.session.'.hash('sha256', $databaseId);
    }

    private function usesDatabaseApiToken(): bool
    {
        $token = config('accurate.access_token');

        return is_string($token) && str_starts_with($token, 'aat.');
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
