<?php

namespace Hippomundo\FileManager\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Class FileManagerServiceProvider
 * @package Hippomundo\FileManager\Providers
 */
class FileManagerServiceProvider extends ServiceProvider
{
    /**
     * @var bool
     */
    protected static $testingMode = false;

    /**
     * @param $value
     */
    public static function test($value)
    {
        static::$testingMode = $value;
    }

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

            if (static::$testingMode) {
                $this->loadMigrationsFrom(realpath(__DIR__.'/../../tests/database/migrations'));
            }
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
