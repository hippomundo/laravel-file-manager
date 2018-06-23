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
        $migrations = realpath(__DIR__.'/../database/migrations');

        if (method_exists($this, 'loadMigrationsFrom')) {
            $this->loadMigrationsFrom($migrations);
        } else {
            $this->publishes([$migrations => database_path().'/migrations']);
        }

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
