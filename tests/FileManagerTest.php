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
        $this->assertTrue($photos->count() === 2);

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
        $resize = [
            'thumbnail'  => ['width' => 50, 'height' => 10],
            'image_size' => ['width' => 1000, 'height' => 1000]
        ];

        $testModel->fileManagerResize($photo->id, $resize);

        $resizedPhoto = $testModel->photo()->first();

        $this->assertTrue(Storage::exists($resizedPhoto->path));
        $this->assertTrue(Storage::exists($resizedPhoto->thumbnail_path));
        $this->assertTrue(Storage::exists($resizedPhoto->original_path));
        $this->assertTrue($photo->path === $resizedPhoto->path);
        $this->assertTrue($photo->thumbnail_path === $resizedPhoto->thumbnail_path);

        $manyPhoto = $photos->first();

        $testModel->fileManagerResize('photos', $manyPhoto->id, $resize);

        $this->assertTrue(Storage::exists($manyPhoto->path));
        $this->assertTrue(Storage::exists($manyPhoto->thumbnail_path));
        $this->assertTrue(Storage::exists($manyPhoto->original_path));
        $this->assertTrue($manyPhoto->path !== $photo->path);
        $this->assertTrue($manyPhoto->thumbnail_path !== $photo->thumbnail_path);

        /*
         * Rename files
         */
        $testModel->fileManagerUpdateNames($photo->id);

        $renamedPhoto = $testModel->photo()->first();

        $this->assertTrue(Storage::exists($renamedPhoto->path));
        $this->assertTrue(Storage::exists($renamedPhoto->thumbnail_path));
        $this->assertTrue(Storage::exists($renamedPhoto->original_path));
        $this->assertTrue($resizedPhoto->path !== $renamedPhoto->path);
        $this->assertTrue($resizedPhoto->thumbnail_path !== $renamedPhoto->thumbnail_path);

        $testModel->fileManagerUpdateNames('photos', $manyPhoto->id);

        $renamedManyPhoto = $testModel->photos()->first();

        $this->assertTrue(Storage::exists($renamedManyPhoto->path));
        $this->assertTrue(Storage::exists($renamedManyPhoto->thumbnail_path));
        $this->assertTrue(Storage::exists($renamedManyPhoto->original_path));
        $this->assertTrue($manyPhoto->path !== $renamedManyPhoto->path);
        $this->assertTrue($manyPhoto->thumbnail_path !== $renamedManyPhoto->thumbnail_path);

        /*
         * Rotate image
         */
        $testModel->fileManagerRotateImage($photo->id, 90);

        $rotatedPhoto = $testModel->photo()->first();

        $this->assertTrue(Storage::exists($rotatedPhoto->path));
        $this->assertTrue(Storage::exists($rotatedPhoto->thumbnail_path));
        $this->assertTrue(Storage::exists($rotatedPhoto->original_path));
        $this->assertTrue($rotatedPhoto->path === $renamedPhoto->path);
        $this->assertTrue($rotatedPhoto->thumbnail_path === $renamedPhoto->thumbnail_path);

        $testModel->fileManagerRotateImage('photos', $manyPhoto->id, 90);

        $rotatedManyPhoto = $testModel->photos()->first();

        $this->assertTrue(Storage::exists($rotatedManyPhoto->path));
        $this->assertTrue(Storage::exists($rotatedManyPhoto->thumbnail_path));
        $this->assertTrue(Storage::exists($rotatedManyPhoto->original_path));
        $this->assertTrue($rotatedManyPhoto->path !== $manyPhoto->path);
        $this->assertTrue($rotatedManyPhoto->thumbnail_path !== $manyPhoto->thumbnail_path);

        /*
         * Update image
         */
        $testModel->update(['photo' => clone $this->photo]);

        $updatedPhoto = $testModel->photo()->first();

        $this->assertTrue(Storage::exists($updatedPhoto->path));
        $this->assertTrue(Storage::exists($updatedPhoto->thumbnail_path));
        $this->assertTrue(Storage::exists($updatedPhoto->original_path));
        $this->assertTrue($updatedPhoto->path !== $rotatedPhoto->path);
        $this->assertTrue($updatedPhoto->thumbnail_path !== $rotatedPhoto->thumbnail_path);

        /*
         * Delete image
         */
        $testModel->fileManagerDeleteFile('photo');

        $deleted = $testModel->photo()->first();

        $this->assertTrue(is_null($deleted));
        $this->assertFalse(Storage::exists($updatedPhoto->path));
        $this->assertFalse(Storage::exists($updatedPhoto->thumbnail_path));
        $this->assertFalse(Storage::exists($updatedPhoto->original_path));
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