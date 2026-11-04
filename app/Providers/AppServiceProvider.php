<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Gate;
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
        TrustProxies::at(config('deployment.trusted_proxies', []));
        if ($this->app->isProduction()) {
            config(['demo.enabled' => false]);
            if (str_starts_with(config('app.url'), 'https://')) {
                URL::forceScheme('https');
            }
        }
        Gate::define('manage-community', fn (User $user) => in_array($user->role, ['super_admin', 'moderator']));
        Gate::define('manage-users', fn (User $user) => $user->role === 'super_admin');
        Gate::define('configure-site', fn (User $user) => $user->role === 'super_admin');
    }
}
