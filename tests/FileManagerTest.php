<?php

namespace RGilyov\CsvImporter\Test;

use RGilyov\FileManager\StorageManager as Storage;
use RGilyov\FileManager\Models\File;
use RGilyov\FileManager\Models\Video;
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

        $resizedManyPhoto = $testModel->photos()->first();

        $this->assertTrue(Storage::exists($resizedManyPhoto->path));
        $this->assertTrue(Storage::exists($resizedManyPhoto->thumbnail_path));
        $this->assertTrue(Storage::exists($resizedManyPhoto->original_path));
        $this->assertTrue($manyPhoto->path !== $resizedManyPhoto->path);
        $this->assertTrue($manyPhoto->thumbnail_path !== $resizedManyPhoto->thumbnail_path);

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
        $this->assertTrue($resizedManyPhoto->path !== $renamedManyPhoto->path);
        $this->assertTrue($resizedManyPhoto->thumbnail_path !== $renamedManyPhoto->thumbnail_path);

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
        $this->assertTrue($rotatedManyPhoto->path !== $renamedManyPhoto->path);
        $this->assertTrue($rotatedManyPhoto->thumbnail_path !== $renamedManyPhoto->thumbnail_path);

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

        $testModel->fileManagerDeleteFile('photos', $manyPhoto->id);

        $photos = $testModel->photos()->get();

        $this->assertTrue($photos->count() === 1);

        /*
         * Create many image
         */
        $testModel->fileManagerSaveFiles([
            'photos'=> [
                clone $this->photo,
                clone $this->photo,
                clone $this->photo,
                clone $this->photo,
                clone $this->photo
            ]
        ]);

        $photos = $testModel->photos()->get();

        $this->assertTrue($photos->count() === 6);
        $photos->each(function (Media $model) {
            $this->assertTrue(Storage::exists($model->path));
            $this->assertTrue(Storage::exists($model->thumbnail_path));
            $this->assertTrue(Storage::exists($model->original_path));
        });

        /*
         * Delete bulk
         */
        $testModel->fileManagerDeleteFile('photos', $photos->slice(0, 3)->pluck('id')->toArray());

        $photos = $testModel->photos()->get();

        $this->assertTrue($photos->count() === 3);

        /*
         * Delete all
         */
        $testModel->fileManagerDeleteFile('photos');

        $photos = $testModel->photos()->get();

        $this->assertTrue($photos->count() === 0);
    }

    /** @test */
    public function it_can_manage_videos()
    {
        /*
         * Attach and save
         */
        $attrs = [
            'name'   => 'test_name',
            'video'  => clone $this->video,
            'videos' => [
                clone $this->video,
                clone $this->video
            ]
        ];

        $testModel = TestModel::create($attrs);

        $video  = $testModel->video()->first();
        $videos = $testModel->videos()->get();

        $this->assertTrue($video instanceof Video);
        $this->assertTrue($videos->count() === 2);

        $this->assertTrue(Storage::exists($video->path));
        $this->assertTrue(Storage::exists($video->original_path));

        $videos->each(function (Video $model) {
            $this->assertTrue(Storage::exists($model->path));
            $this->assertTrue(Storage::exists($model->original_path));
        });

        /*
         * Resize
         */
        $resize = ['resize' => '720p30'];

        $testModel->fileManagerResize('video', $resize);

        $resizedVideo = $testModel->video()->first();

        $this->assertTrue(Storage::exists($resizedVideo->path));
        $this->assertTrue(Storage::exists($resizedVideo->original_path));

        $manyVideo = $videos->first();

        $testModel->fileManagerResize('videos', $manyVideo->id, $resize);

        $resizedManyVideos = $testModel->videos()->first();

        $this->assertTrue(Storage::exists($resizedManyVideos->path));
        $this->assertTrue(Storage::exists($resizedManyVideos->original_path));

        /*
         * Rename files
         */
        $testModel->fileManagerUpdateNames('video');

        $renamedVideo = $testModel->video()->first();

        $this->assertTrue(Storage::exists($renamedVideo->path));
        $this->assertTrue(Storage::exists($renamedVideo->original_path));
        $this->assertTrue($resizedVideo->path !== $renamedVideo->path);

        $testModel->fileManagerUpdateNames('videos', $manyVideo->id);

        $renamedManyVideo = $testModel->videos()->first();

        $this->assertTrue(Storage::exists($renamedManyVideo->path));
        $this->assertTrue(Storage::exists($renamedManyVideo->original_path));
        $this->assertTrue($manyVideo->path !== $renamedManyVideo->path);

        /*
         * Update video
         */
        $testModel->update(['video' => clone $this->video]);

        $updatedVideo = $testModel->video()->first();

        $this->assertTrue(Storage::exists($updatedVideo->path));
        $this->assertTrue(Storage::exists($updatedVideo->original_path));
        $this->assertTrue($updatedVideo->path !== $renamedVideo->path);

        /*
         * Delete video
         */
        $testModel->fileManagerDeleteFile('video');

        $deleted = $testModel->video()->first();

        $this->assertTrue(is_null($deleted));
        $this->assertFalse(Storage::exists($updatedVideo->path));
        $this->assertFalse(Storage::exists($updatedVideo->original_path));

        $testModel->fileManagerDeleteFile('videos', $manyVideo->id);

        $videos = $testModel->videos()->get();

        $this->assertTrue($videos->count() === 1);

        /*
         * Create many videos
         */
        $testModel->fileManagerSaveFiles([
            'videos'=> [
                clone $this->video,
                clone $this->video,
                clone $this->video,
                clone $this->video,
                clone $this->video
            ]
        ]);

        $videos = $testModel->videos()->get();

        $this->assertTrue($videos->count() === 6);
        $videos->each(function (Video $model) {
            $this->assertTrue(Storage::exists($model->path));
            $this->assertTrue(Storage::exists($model->original_path));
        });

        /*
         * Delete bulk
         */
        $testModel->fileManagerDeleteFile('videos', $videos->slice(0, 3)->pluck('id')->toArray());

        $videos = $testModel->videos()->get();

        $this->assertTrue($videos->count() === 3);

        /*
         * Delete all
         */
        $testModel->fileManagerDeleteFile('videos');

        $videos = $testModel->videos()->get();

        $this->assertTrue($videos->count() === 0);
    }

    /** @test */
    public function it_can_manage_files()
    {
        /*
         * Attach and save
         */
        $attrs = [
            'name'        => 'test_name',
            'file'        => clone $this->file,
            'super_files' => [
                clone $this->file,
                clone $this->file
            ]
        ];

        $testModel = TestModel::create($attrs);

        $file  = $testModel->file()->first();
        $files = $testModel->files()->get();

        $manyFile = $files->first();

        $this->assertTrue($file instanceof File);
        $this->assertTrue($files->count() === 2);

        $this->assertTrue(Storage::exists($file->path));

        $files->each(function (File $model) {
            $this->assertTrue(Storage::exists($model->path));
        });

        /*
         * Rename files
         */
        $testModel->fileManagerUpdateNames('file');

        $renamedFile = $testModel->file()->first();

        $this->assertTrue(Storage::exists($renamedFile->path));
        $this->assertTrue($file->path !== $renamedFile->path);

        $testModel->fileManagerUpdateNames('files', $manyFile->id);

        $renamedManyFile = $testModel->files()->first();

        $this->assertTrue(Storage::exists($renamedManyFile->path));
        $this->assertTrue($manyFile->path !== $renamedManyFile->path);

        /*
         * Update video
         */
        $testModel->update(['file' => clone $this->file]);

        $updatedFile = $testModel->file()->first();

        $this->assertTrue(Storage::exists($updatedFile->path));
        $this->assertTrue($updatedFile->path !== $renamedFile->path);

        /*
         * Delete file
         */
        $testModel->fileManagerDeleteFile('file');

        $deleted = $testModel->file()->first();

        $this->assertTrue(is_null($deleted));
        $this->assertFalse(Storage::exists($updatedFile->path));

        $testModel->fileManagerDeleteFile('files', $manyFile->id);

        $files = $testModel->files()->get();

        $this->assertTrue($files->count() === 1);

        /*
         * Create many files
         */
        $testModel->fileManagerSaveFiles([
            'super_files' => [
                clone $this->file,
                clone $this->file,
                clone $this->file,
                clone $this->file,
                clone $this->file
            ]
        ]);

        $files = $testModel->files()->get();

        $this->assertTrue($files->count() === 6);
        $files->each(function (File $model) {
            $this->assertTrue(Storage::exists($model->path));
        });

        /*
         * Delete bulk
         */
        $testModel->fileManagerDeleteFile('files', $files->slice(0, 3)->pluck('id')->toArray());

        $files = $testModel->files()->get();

        $this->assertTrue($files->count() === 3);

        /*
         * Delete all
         */
        $testModel->fileManagerDeleteFile('files');

        $files = $testModel->files()->get();

        $this->assertTrue($files->count() === 0);
    }
}
