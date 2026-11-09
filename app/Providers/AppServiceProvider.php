<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.codespaces') && app()->bound('request')) {
            $request = request();
            $forwardedHost = $request->header('X-Forwarded-Host');
            $host = $forwardedHost ?: $request->getHost();
            $scheme = $request->header('X-Forwarded-Proto', $request->getScheme());

            URL::forceRootUrl(sprintf('%s://%s', $scheme, $host));
            URL::forceScheme($scheme);
        }
    }
}
