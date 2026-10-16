<?php

namespace App\Providers;

use App\Models\User;
use App\Services\PermissionCatalogue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
    public function boot(PermissionCatalogue $permissions): void
    {
        Paginator::useBootstrapFive();

        Model::preventLazyLoading(! $this->app->isProduction());

        Password::defaults(fn () => Password::min(1));

        // Every name in config/permissions.php (e.g. "clients.create") is a gate checked against the user's role,
        // so views and controllers can use @can('clients.create') / Gate::authorize('clients.create').
        Gate::before(function (User $user, string $ability) use ($permissions): ?bool {
            if ($permissions->exists($ability)) {
                return $user->hasPermission($ability);
            }

            return null;
        });
    }
}
