<?php

namespace RGilyov\CsvImporter\Test;

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
                clone $this->photo,
                clone $this->photo
            ]
        ];

        $testModel = TestModel::create($attrs);

        $this->assertTrue($testModel->photo instanceof Media);
        $this->assertTrue($testModel->photos->count() === 3);
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