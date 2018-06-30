<?php

namespace RGilyov\FileManager;

use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Illuminate\Support\Arr;
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
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function saveFile(UploadedFile $file)
    {
        $type           = $file->getMimeType();
        $file_size      = $file->getClientSize();
        $path           = $this->saveImage($file);
        $url            = $this->pathToUrl($path);
        $thumbnail_path = $this->saveThumbnail($file);
        $thumbnail_url  = $this->pathToUrl($thumbnail_path);
        $folder_path    = $this->mainFolder($file);
        $original_name  = StorageManager::originalName($file);
        $storage        = $this->getStorageName();
        $extension      = StorageManager::extension($file);
        $hash           = $this->makeHash($original_name);
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
     */
    public function resize(Mediable $model, $sizes)
    {
        $this->checkOriginal($model->original_path);

        $imageSizes     = Arr::get($sizes, 'image_size', $this->getImageSizes());
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

        if (is_null($value)) {
            return false;
        }

        return Arr::get($this->config, 'update_names_on_change');
    }

    /**
     * @return mixed
     */
    public function getThumbnailSizes()
    {
        return Arr::get($this->config, 'thumbnail', 250);
    }

    /**
     * @return mixed
     */
    public function getImageSizes()
    {
        return Arr::get($this->config, 'image_size', 500);
    }

    /**
     * @param Mediable|Media $model
     * @return Mediable
     * @throws FileManagerException
     */
    public function updateFileNames(Mediable $model)
    {
        $path           = StorageManager::generateUniquePath($model->path);
        $thumbnail_path = StorageManager::generateUniquePath($model->thumbnail_path);

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
        if (StorageManager::exists($path)) {

            StorageManager::tmpScope($path, function ($tmp) use ($value, $path) {
                $image = new ImageManager();

                $contents = ( string )$image->make($tmp)->rotate($this->rotationValue($value))->encode();

                $this->putFileToPath($path, $contents);
            });

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
        return strcasecmp(StorageManager::extension($file), 'svg') === 0;
    }

    /**
     * @param $file
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
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
     */
    protected function resizeAndSave($file, $sizes)
    {
        $path = StorageManager::generateUniquePath($file);

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
            return StorageManager::tmpScope($file, function ($tmp) use ($width, $height) {
                $image = new ImageManager();

                return (string) $image->make($tmp)
                    ->resize($width, $height, function ($constraint) {
                        $constraint->aspectRatio();
                    })
                    ->encode();
            });
        }
    }
}
