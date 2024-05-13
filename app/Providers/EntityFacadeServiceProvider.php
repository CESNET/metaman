<?php

namespace App\Providers;

use App\Services\EntityService;
use Illuminate\Support\ServiceProvider;

class EntityFacadeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('entity', function ($app) {
            return new EntityService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
