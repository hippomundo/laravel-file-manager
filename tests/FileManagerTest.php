<?php

namespace RGilyov\CsvImporter\Test;

use Illuminate\Support\Facades\Storage;
use RGilyov\FileManager\Test\BaseTestCase;
use RGilyov\FileManager\Models\Media;
use RGilyov\FileManager\Test\Models\TestModel;

/**
 * Class FileManagerTest
 * @package RGilyov\CsvImporter\Test
 */
class FileManagerTest extends BaseTestCase
{
    /** @test */
    public function it_can_manage_images()
    {
        /*
         * Attach and save images
         */
        $attrs = [
            'name'   => 'test_name',
            'photo'  => clone $this->photo,
            'photos' => [
                clone $this->photo,
                clone $this->photo
            ]
        ];

        $testModel = TestModel::create($attrs);

        $photo  = $testModel->photo()->first();
        $photos = $testModel->photos()->get();

        $this->assertTrue($photo instanceof Media);
        $this->assertTrue($photos->count() === 3);

        $this->assertTrue(Storage::exists($photo->path));
        $this->assertTrue(Storage::exists($photo->thumbnail_path));
        $this->assertTrue(Storage::exists($photo->original_path));

        $photos->each(function (Media $model) {
            $this->assertTrue(Storage::exists($model->path));
            $this->assertTrue(Storage::exists($model->thumbnail_path));
            $this->assertTrue(Storage::exists($model->original_path));
        });

        /*
         * Resize images
         */

        $testModel->fileManagerResize('photo', ['width' => 50, 'height' => 10]);

        $newPhoto = $testModel->photo()->first();

        $this->assertTrue(Storage::exists($photo->path));
        $this->assertTrue(Storage::exists($photo->thumbnail_path));
        $this->assertTrue(Storage::exists($photo->original_path));
        $this->assertTrue($photo->path !== $newPhoto->path);
        $this->assertTrue($photo->thumbnail_path !== $newPhoto->thumbnail_path);
        $this->assertTrue($photo->original_path !== $newPhoto->original_path);


    }

    /** @test */
    public function it_can_manage_videos()
    {

    }

    /** @test */
    public function it_can_manage_files()
    {

    }
}