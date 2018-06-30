<?php

namespace RGilyov\FileManager\Test;

use RGilyov\FileManager\StorageManager;
use RGilyov\FileManager\Test\File\UploadedFile;
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

        try {
            $this->artisan(
                'migrate',
                ['--database' => 'testbench', '--realpath' => realpath(__DIR__.'/../src/database/migrations')]
            );
            $this->artisan(
                'migrate',
                ['--database' => 'testbench', '--realpath' => realpath(__DIR__.'/database/migrations')]
            );
        } catch (\Exception $e) {
            $this->artisan('migrate', ['--database' => 'testbench']);
        }

        $this->photo = $this->generateUploadedFile('test_image.png');
        $this->video = $this->generateUploadedFile('test_video.mp4');
        $this->file  = $this->generateUploadedFile('test_file.txt');
    }

    /**
     * @param $file_name
     * @return UploadedFile
     * @throws \Exception
     */
    protected function generateUploadedFile($file_name)
    {
        $file_path = __DIR__ . "/files/" . $file_name;
        $finfo = new finfo(FILEINFO_MIME_TYPE);

        if (is_file($file_path)) {
            return new UploadedFile(
                $file_path,
                $file_name,
                $finfo->file($file_path),
                filesize($file_path),
                0,
                true
            );
        }

        throw new \Exception("File {$file_path} not found");
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        FileManagerServiceProvider::test(true);

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
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $app['config']->set('file-manager.backup_disk', 'backup');
        $app['config']->set('filesystems.disks', [
            'backup' => [
                'driver' => 'local',
                'root'   => storage_path('backup'),
            ],
            'local' => [
                'driver' => 'local',
                'root'   => storage_path('app'),
            ],
        ]);
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

        @StorageManager::deleteDirectory('files');

        parent::tearDown();
    }
}
