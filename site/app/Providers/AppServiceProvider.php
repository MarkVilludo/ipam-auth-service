<?php

namespace App\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Set JWT secret as early as possible so tokens use the same secret as the IP service.
        $this->ensureJwtSecretFromEnvironment();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->ensureJwtNumericConfig();
    }

    /**
     * Prefer JWT_SECRET from runtime environment (e.g. Docker) so auth and IP service always match.
     * Fall back to .env when running locally.
     */
    protected function ensureJwtSecretFromEnvironment(): void
    {
        $secret = getenv('JWT_SECRET');
        if ($secret !== false && $secret !== '') {
            Config::set('jwt.secret', $secret);
            return;
        }
        $fromEnv = env('JWT_SECRET');
        if ($fromEnv !== null && $fromEnv !== '') {
            Config::set('jwt.secret', $fromEnv);
        }
    }

    /**
     * Force JWT time config to integers so Carbon never receives strings (env() returns strings).
     * Fixes: Carbon\Carbon::rawAddUnit() Argument #3 ($value) must be of type int|float, string given
     */
    protected function ensureJwtNumericConfig(): void
    {
        Config::set('jwt.ttl', (int) config('jwt.ttl', 60));
        Config::set('jwt.refresh_ttl', (int) config('jwt.refresh_ttl', 20160));
        Config::set('jwt.leeway', (int) config('jwt.leeway', 0));
        Config::set('jwt.blacklist_grace_period', (int) config('jwt.blacklist_grace_period', 0));
    }
}
