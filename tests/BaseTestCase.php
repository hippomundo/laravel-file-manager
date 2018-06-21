<?php

namespace RGilyov\FileManager\Test;

use Illuminate\Http\UploadedFile;
use Orchestra\Testbench\TestCase;
use RGilyov\FileManager\Models\File;
use RGilyov\FileManager\Models\Media;
use RGilyov\FileManager\Models\Video;
use RGilyov\FileManager\Providers\FileManagerServiceProvider;
use finfo;

/**
 * Class BaseTestCase
 * @package RGilyov\FileManage\Test
 */
abstract class BaseTestCase extends TestCase
{
    /**
     * @var UploadedFile
     */
    protected $photo;

    /**
     * @var UploadedFile
     */
    protected $video;

    /**
     * @var UploadedFile
     */
    protected $file;

    /**
     * @throws \Exception
     */
    public function setUp()
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        $this->artisan('migrate', ['--database' => 'testbench']);

        $this->photo = $this->generateUploadedFile('/files/test_image.png');
        $this->video = $this->generateUploadedFile('/files/test_video.mp4');
        $this->file  = $this->generateUploadedFile('/files/test_file.txt');
    }

    /**
     * @param $file_name
     * @return UploadedFile
     * @throws \Exception
     */
    protected function generateUploadedFile($file_name)
    {
        $file_name = __DIR__ . $file_name;

        $file_path = storage_path($file_name);
        $finfo = new finfo(FILEINFO_MIME_TYPE);

        if (is_file($file_name)) {
            return new UploadedFile(
                $file_path,
                $file_name,
                $finfo->file($file_path),
                filesize($file_path),
                0,
                false
            );
        }

        throw new \Exception("File {$file_name} not found");
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
