<?php

namespace RGilyov\FileManage\Test;

use Orchestra\Testbench\TestCase;
use RGilyov\FileManager\Models\File;
use RGilyov\FileManager\Models\Media;
use RGilyov\FileManager\Models\Video;
use RGilyov\Providers\FileManagerServiceProvider;

/**
 * Class BaseTestCase
 * @package RGilyov\FileManage\Test
 */
abstract class BaseTestCase extends TestCase
{
    /**
     * @throws \Exception
     */
    public function setUp()
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        $this->artisan('migrate', ['--database' => 'testbench']);


    }

    /**
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            FileManagerServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
    }

    /**
     * @return void
     */
    public function tearDown()
    {
        Media::all()->each(function (Media $model) {
            $model->delete();
        });

        Video::all()->each(function (Video $model) {
            $model->delete();
        });

        File::all()->each(function (File $model) {
            $model->delete();
        });

        parent::tearDown();
    }
}
