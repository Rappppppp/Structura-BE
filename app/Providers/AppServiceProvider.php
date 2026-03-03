<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
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
        // Define a simple admin gate based on `role` column.
        Gate::define('admin', function ($user) {
            return $user && isset($user->role) && strtolower($user->role) === 'admin';
        });
    }
}
