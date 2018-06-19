<?php

namespace RGilyov\FileManager;

use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use RGilyov\FileManager\Interfaces\Mediable;
use RGilyov\FileManager\Models\Media;

/**
 * Class MediaManager
 * @package RGilyov\FileManager
 */
class MediaManager extends BaseManager
{
    /**
     * @return mixed
     */
    public function defaultConfig()
    {
        return config('file-manager.media.default');
    }

    /**
     * @return Media
     */
    public function initModel()
    {
        return new Media;
    }

    /**
     * @param UploadedFile $file
     * @return array
     * @throws FileManagerException
     */
    protected function saveFile(UploadedFile $file)
    {
        $type           = $file->getMimeType();
        $file_size      = $file->getClientSize();
        $path           = $this->saveImage($file);
        $url            = $this->pathToUrl($path);
        $thumbnail_path = $this->saveThumbnail($file);
        $thumbnail_url  = $this->pathToUrl($thumbnail_path);
        $folder_path    = $this->mainFolder();
        $origin_name    = $this->originalName($file);
        $storage        = $this->getStorageName();
        $extension      = $this->extension($file);
        $hash           = $this->makeHash($origin_name);
        $original_path  = $this->moveOriginal($file);
        $original_url   = $this->pathToUrl($original_path);

        return compact(
            'type',
            'file_size',
            'path',
            'url',
            'thumbnail_path',
            'thumbnail_url',
            'original_path',
            'original_url',
            'folder_path',
            'origin_name',
            'storage',
            'extension',
            'hash'
        );
    }

    /**
     * @param Mediable|Media $model
     * @param null $imageSizes
     * @param null $thumbnailSizes
     * @return Mediable
     * @throws FileManagerException
     */
    public function resize(Mediable $model, $imageSizes = null, $thumbnailSizes = null)
    {
        $imageSizes     = $imageSizes ? $imageSizes : Arr::get($this->config, 'image_size', 500);
        $thumbnailSizes = $thumbnailSizes ? $thumbnailSizes : Arr::get($this->config, 'thumbnail', 250);

        if (Storage::exists($model->original_path)) {
            $resized = $this->resizeFile($model->original_path, $imageSizes);

            $model->deleteImage();

            $this->saveImageFile($model->path, $resized);

            $resized = $this->resizeFile($model->original_path, $thumbnailSizes);

            $model->deleteThumbnail();

            $this->saveImageFile($model->thumbnail_path, $resized);

            if ($this->updateNamesOnChange()) {
                return $this->updateFileNames($model);
            }

            return $model;
        }

        throw new FileManagerException('Original file does not exists');
    }

    /**
     * @return mixed
     */
    protected function updateNamesOnChange()
    {
        return Arr::get($this->config, 'update_names_on_change', false);
    }

    /**
     * @param Mediable|Media $model
     * @return Mediable
     * @throws FileManagerException
     */
    public function updateFileNames(Mediable $model)
    {
        $path           = $this->generateUniquePathFromExisting($model->path);
        $thumbnail_path = $this->generateUniquePathFromExisting($model->thumbnail_path);

        $this->renameFile($model->path, $path);
        $this->renameFile($model->thumbnail_path, $thumbnail_path);

        $url           = $this->pathToUrl($path);
        $thumbnail_url = $this->pathToUrl($thumbnail_path);

        $model->update(compact('path', 'thumbnail_path', 'url', 'thumbnail_url'));

        return $model;
    }

    /**
     * @param Mediable|Media $model
     * @param $value
     * @return Mediable
     * @throws FileManagerException
     * @throws \Exception
     */
    public function rotate(Mediable $model, $value)
    {
        if ($this->isSvg($model->original_path)) {
            return $model;
        }

        $this->rotatePath($model->path, $value);
        $this->rotatePath($model->thumbnail_path, $value);

        if ($this->updateNamesOnChange()) {
            return $this->updateFileNames($model);
        }

        return $model;
    }

    /**
     * @param $path
     * @param $value
     * @return bool
     * @throws \Exception
     */
    public function rotatePath($path, $value)
    {
        if (Storage::exists($path)) {

            $image = new ImageManager();

            $contents = ( string )$image->make($path)->rotate($this->rotationValue($value))->encode();

            Storage::delete($path);

            $this->saveImageFile($path, $contents);

            return $path;
        }

        return false;
    }

    /**
     * @param $value
     * @return int
     */
    protected function rotationValue($value)
    {
        if (!is_numeric($value) && is_string($value)) {
            switch ($value) {
                case 'right':
                    $value = 270;
                    break;
                case 'left':
                    $value = 90;
                    break;
                case 'turn':
                case 'roll':
                    $value = 180;
                    break;
            }
        }

        return $value;
    }

    /**
     * @param $file
     * @return bool
     */
    public function isSvg($file)
    {
        return strcasecmp($this->extension($file), 'svg') === 0;
    }

    /**
     * @param $file
     * @return string
     * @throws FileManagerException
     */
    protected function saveImage($file)
    {
        $sizes = Arr::get($this->config, 'image_size', 500);

        return $this->resizeAndSave($file, $sizes);
    }

    /**
     * @param $file
     * @return string
     * @throws FileManagerException
     */
    protected function saveThumbnail($file)
    {
        $sizes = Arr::get($this->config, 'thumbnail', 250);

        return $this->resizeAndSave($file, $sizes);
    }

    /**
     * @param $file
     * @param $sizes
     * @return string
     * @throws FileManagerException
     */
    protected function resizeAndSave($file, $sizes)
    {
        $path = $this->generateUniquePath($file);

        $resized = $this->resizeFile($file, $sizes);

        $this->saveImageFile($path, $resized);

        return $path;
    }

    /**
     * @param $path
     * @param $contents
     * @throws FileManagerException
     */
    protected function saveImageFile($path, $contents)
    {
        if (! Storage::put($path, $contents)) {
            throw new FileManagerException('Was not able to save image');
        }
    }

    /**
     * @param $file
     * @param null $sizes
     * @return string
     */
    protected function resizeFile($file, $sizes = null)
    {
        if (is_array($sizes)) {
            $width  = Arr::get($sizes, 'width', null);
            $height = Arr::get($sizes, 'height', null);
        } else {
            $width  = ( int )$sizes;
            $height = null;
        }

        if ($this->isSvg($file)) {
            return File::get($file);
        } else {
            $image = new ImageManager();

            return (string) $image->make($file)
                ->resize($width, $height, function ($constraint) {
                    $constraint->aspectRatio();
                })
                ->encode();
        }
    }
}
