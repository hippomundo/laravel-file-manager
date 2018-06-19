<?php

namespace RGilyov\FileManager;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use RGilyov\FileManager\Interfaces\Mediable;
use RGilyov\FileManager\Models\File;
use RGilyov\FileManager\Models\Media;
use RGilyov\FileManager\Models\Video;
use \RGilyov\FileManager\Interfaces\Manager as ManagerContract;

class Manager implements ManagerContract
{
    public function __construct(BaseManager $manager)
    {
        
    }

    /**
     * @param UploadedFile $file
     * @return Media|Video|File
     */
    public function create(UploadedFile $file)
    {

    }

    /**
     * @param UploadedFile $file
     * @param Mediable $model
     * @return Media|Video|File
     */
    public function update(UploadedFile $file, Mediable $model)
    {

    }

    /**
     * @param Mediable|Model $model
     * @return bool
     */
    public function delete(Mediable $model)
    {

    }

    /**
     * @param Mediable $model
     * @param $value
     * @return Media
     */
    public function rotate(Mediable $model, $value)
    {

    }

    /**
     * @param Mediable $model
     * @param array ...$sizes
     * @return Model
     */
    public function resize(Mediable $model, ...$sizes)
    {

    }

    /**
     * @param Mediable $model
     * @return Model|Mediable
     */
    public function updateFileNames(Mediable $model)
    {

    }
}
