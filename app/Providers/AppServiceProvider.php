<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register ProductRepository
        $this->app->singleton(\App\Repositories\ProductRepository::class);
        
        // Register ProductService
        $this->app->singleton(\App\Services\ProductService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
