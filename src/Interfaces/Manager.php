<?php

namespace RGilyov\FileManager\Interfaces;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use RGilyov\FileManager\Models\File;
use RGilyov\FileManager\Models\Media;
use RGilyov\FileManager\Models\Video;

/**
 * Interface Manager
 * @package RGilyov\FileManager\Interfaces
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
     * @return Media
     */
    public function rotate(Mediable $model);

    /**
     * @param Mediable $model
     * @return Mediable|Model
     */
    public function resize(Mediable $model);

    /**
     * @param Mediable $model
     * @return Model|Mediable
     */
    public function updateFileNames(Mediable $model);
}