<?php

namespace Ranjith\LaravelWebpConverter;

use Illuminate\Support\ServiceProvider;

class WebPConverterServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__.'/../config/webp.php', 'webp'
        );

        // Register the main class
        $this->app->singleton('webp-converter', function ($app) {
            return new WebPConverter();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../config/webp.php' => config_path('webp.php'),
        ], 'webp-config');
    }
}