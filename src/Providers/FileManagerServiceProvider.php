<?php

namespace RGilyov\FileManager\Providers;

use Illuminate\Support\ServiceProvider;

class FileManagerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/file-manager.php' => config_path('file-manager.php')
        ], 'config');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/file-manager.php', 'file-manager');

        if (method_exists($this, 'commands')) {
            $this->commands([

            ]);
        }
    }
}
