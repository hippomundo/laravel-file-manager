<?php

namespace Hippomundo\FileManager\Interfaces;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Hippomundo\FileManager\Models\File;
use Hippomundo\FileManager\Models\Media;
use Hippomundo\FileManager\Models\Video;

/**
 * Interface Manager
 * @package Hippomundo\FileManager\Interfaces
 */
interface Manager
{
    /**
     * @param UploadedFile $file
     * @return Media|Video|File
     */
    public function create(UploadedFile $file);

    /**
     * @param UploadedFile $file
     * @param Mediable $model
     * @return Media|Video|File
     */
    public function update(UploadedFile $file, Mediable $model);

    /**
     * @param Mediable|Model $model
     * @return bool
     */
    public function delete(Mediable $model);

    /**
     * @param Mediable $model
     * @param $value
     * @return Mediable
     */
    public function rotate(Mediable $model, $value);

    /**
     * @param Mediable|Model $model
     * @param $sizes
     * @return Model
     */
    public function resize(Mediable $model, $sizes);

    /**
     * @param Mediable $model
     * @return Model|Mediable
     */
    public function updateFileNames(Mediable $model);
}