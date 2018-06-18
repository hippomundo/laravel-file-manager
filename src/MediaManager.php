<?php

namespace RGilyov\FileManager;

use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
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
     * @return $this|\Illuminate\Database\Eloquent\Model
     * @throws FileManagerException
     */
    public function create(UploadedFile $file)
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
        $extension      = $file->extension();
        $hash           = $this->makeHash($origin_name);
        $original_path  = $this->moveOriginal($file);
        $original_url   = $this->pathToUrl($original_path);

        return $this->model->create(compact(
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
        ));
    }

    /**
     * @param $file
     * @return bool
     */
    public function isSvg($file)
    {
        $path = ($file instanceof UploadedFile) ? $file->getClientOriginalName() : $file;

        return strcasecmp($this->extension($path), 'svg') === 0;
    }

    /**
     * @param $file
     * @return string
     * @throws FileManagerException
     */
    protected function saveImage($file)
    {
        $path = $this->generateUniquePath($file);

        $max_width = Arr::get($this->config, 'max_size', 500);

        $resized = $this->resize($file, $max_width);

        $this->saveImageFile($path, $resized);

        return $path;
    }

    /**
     * @param $file
     * @return string
     * @throws FileManagerException
     */
    protected function saveThumbnail($file)
    {
        $path = $this->generateUniquePath($file);

        $width = Arr::get($this->config, 'thumbnail.width', 250);

        $height = Arr::get($this->config, 'thumbnail.height', 250);

        $resized = $this->resize($file, $width, $height);

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
     * @param null $width
     * @param null $height
     * @return string
     */
    protected function resize($file, $width = null, $height = null)
    {
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
