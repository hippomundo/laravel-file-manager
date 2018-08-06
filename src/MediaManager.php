<?php

namespace Hippomundo\FileManager;

use Illuminate\Support\Facades\File;
use Hippomundo\FileManager\Exceptions\FileManagerException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Illuminate\Support\Arr;
use Intervention\Image\ImageManager;
use Hippomundo\FileManager\Interfaces\Mediable;
use Hippomundo\FileManager\Models\Media;

/**
 * Class MediaManager
 * @package Hippomundo\FileManager
 */
class MediaManager extends BaseManager
{
    /**
     * @var string
     */
    protected $mimeType;

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
     * @param $mimeType
     * @return void
     */
    public function initMimeType($mimeType)
    {
        $this->mimeType = $mimeType;
    }

    /**
     * @param UploadedFile $file
     * @return array
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \ReflectionException
     */
    protected function saveFile(UploadedFile $file)
    {
        $this->initMimeType($file->getMimeType());

        $type           = $this->mimeType;
        $file_size      = $file->getClientSize();
        $path           = $this->saveImage($file);
        $thumbnail_path = $this->saveThumbnail($file);
        $folder_path    = $this->mainFolder($file);
        $original_name  = StorageManager::originalName($file);
        $storage        = $this->getStorageName();
        $extension      = StorageManager::extension($file);
        $hash           = $this->makeHash($original_name);
        $original_path  = $this->moveOriginal($file);

        return compact(
            'type',
            'file_size',
            'path',
            'thumbnail_path',
            'original_path',
            'folder_path',
            'original_name',
            'storage',
            'extension',
            'hash'
        );
    }

    /**
     * @param Mediable|Media $model
     * @param array $sizes
     * @return \Illuminate\Database\Eloquent\Model|Mediable
     * @throws FileManagerException
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \ReflectionException
     */
    public function resize(Mediable $model, $sizes)
    {
        $this->initMimeType($model->type);

        if (! $this->canBeTransformed()) {
            return $model;
        }

        $this->checkOriginal($model->original_path);

        $imageSizes     = $this->getImageSizes($sizes);
        $thumbnailSizes = Arr::get($sizes, 'thumbnail', $this->getThumbnailSizes());

        $resized = $this->resizeFile($model->original_path, $imageSizes);

        $model->deleteImage();

        $this->putFileToPath($model->path, $resized);

        $resized = $this->resizeFile($model->original_path, $thumbnailSizes);

        $model->deleteThumbnail();

        $this->putFileToPath($model->thumbnail_path, $resized);

        if ($this->updateNamesOnChange()) {
            return $this->updateFileNames($model);
        }

        return $model;
    }

    /**
     * @return mixed
     */
    protected function updateNamesOnChange()
    {
        $value = Arr::get($this->config, 'update_names_on_change');

        return is_null($value) ? true : $value;
    }

    /**
     * @return mixed
     */
    public function getThumbnailSizes()
    {
        return Arr::get($this->config, 'thumbnail', 250);
    }

    /**
     * @param $sizes
     * @return array|mixed
     */
    public function getImageSizes($sizes = null)
    {
        $default = Arr::get($this->config, 'image_size', 500);

        if (! $sizes) {
            return $default;
        }

        return Arr::get($sizes, 'image_size', $default);
    }

    /**
     * @param Mediable|Media $model
     * @return \Illuminate\Database\Eloquent\Model|Mediable
     */
    public function updateFileNames(Mediable $model)
    {
        $path           = StorageManager::generateUniquePath($model->path);
        $thumbnail_path = StorageManager::generateUniquePath($model->thumbnail_path);

        $this->renameFile($model->path, $path);
        $this->renameFile($model->thumbnail_path, $thumbnail_path);

        $model->update(compact('path', 'thumbnail_path'));

        return $model;
    }

    /**
     * @param Mediable|Media $model
     * @param $value
     * @return Mediable
     * @throws \Exception
     */
    public function rotate(Mediable $model, $value)
    {
        $this->initMimeType($model->type);

        if (! $this->canBeTransformed()) {
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
        if (! $this->canBeTransformed()) {
            return $path;
        }

        if (StorageManager::exists($path)) {
            if (StorageManager::isSingleCloudDisk()) {
                $contents = StorageManager::tmpScope($path, function ($tmp) use ($value) {
                    return $this->rotateImage($tmp, $value);
                });
            } else {
                $contents = $this->rotateImage(StorageManager::originalFullPath($path), $value);
            }

            $this->putFileToPath($path, $contents);

            return $path;
        }

        return false;
    }

    /**
     * @param $path
     * @param $value
     * @return string
     */
    protected function rotateImage($path, $value)
    {
        $image = new ImageManager();

        return ( string )$image->make($path)->rotate($this->rotationValue($value))->encode();
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
     * @return bool
     */
    public function canBeTransformed()
    {
        switch (strtolower($this->mimeType)) {
            case 'image/png':
            case 'image/x-png':
            case 'image/jpg':
            case 'image/jpeg':
            case 'image/pjpeg':
            case 'image/gif':
                return true;
            case 'image/webp':
            case 'image/x-webp':
                return function_exists('imagecreatefromwebp');
            default:
                return false;
        }
    }

    /**
     * @param $file
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \ReflectionException
     */
    protected function saveImage($file)
    {
        $sizes = $this->getImageSizes();

        return $this->resizeAndSave($file, $sizes);
    }

    /**
     * @param $file
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \ReflectionException
     */
    protected function saveThumbnail($file)
    {
        $sizes = $this->getThumbnailSizes();

        return $this->resizeAndSave($file, $sizes);
    }

    /**
     * @param $file
     * @param $sizes
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \ReflectionException
     */
    protected function resizeAndSave($file, $sizes)
    {
        $path = StorageManager::generateUniquePath($file, $this->mainFolder($file));

        $resized = $this->resizeFile($file, $sizes);

        $this->putFileToPath($path, $resized);

        return $path;
    }

    /**
     * @param $file
     * @param null $sizes
     * @return mixed
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function resizeFile($file, $sizes = null)
    {
        if (! $this->canBeTransformed()) {
            return File::get($file);
        }

        if (is_array($sizes)) {
            $width  = Arr::get($sizes, 'width', null);
            $height = Arr::get($sizes, 'height', null);
        } else {
            $width  = ( int )$sizes;
            $height = null;
        }

        if (StorageManager::isSingleCloudDisk()) {
            return StorageManager::tmpScope($file, function ($tmp) use ($width, $height) {
                return $this->resizeImage($tmp, $width, $height);
            });
        }

        return $this->resizeImage(StorageManager::originalFullPath($file), $width, $height);
    }

    /**
     * @param $file
     * @param $width
     * @param $height
     * @return string
     */
    protected function resizeImage($file, $width, $height)
    {
        $image = (new ImageManager())->make($file);

        return ( string )$image->resize($width, $height, function ($constraint) {
            $constraint->aspectRatio();
        })->encode();
    }
}
