<?php

namespace RGilyov\FileManager;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Illuminate\Support\Arr;
use RGilyov\FileManager\Interfaces\Mediable;
use RGilyov\FileManager\Models\Video;

/**
 * Class VideoManager
 * @package RGilyov\FileManager
 */
class VideoManager extends BaseManager
{
    /**
     * @return mixed
     */
    public function defaultConfig()
    {
        return config('file-manager.video.default');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model|Video
     */
    public function initModel()
    {
        return new Video();
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
        $folder_path    = $this->mainFolder($file);
        $original_name  = $this->originalName($file);
        $storage        = $this->getStorageName();
        $extension      = $this->extension($file);
        $hash           = $this->makeHash($original_name);
        $original_path  = $this->moveOriginal($file);
        $original_url   = $this->pathToUrl($original_path);
        $path           = $this->saveVideo($original_path);
        $url            = $this->pathToUrl($path);

        return compact(
            'original_path',
            'original_url',
            'path',
            'url',
            'folder_path',
            'original_name',
            'extension',
            'hash',
            'storage',
            'type',
            'file_size'
        );
    }

    /**
     * @param $original_path
     * @return string
     */
    public function saveVideo($original_path)
    {
        $path = $this->generateUniquePathFromExisting($original_path);

        $size = $this->getResize();

        return $this->resizeAndSaveVideo($original_path, $path, $size);
    }

    /**
     * @param $fromPath
     * @param $toPath
     * @param $size
     * @return string
     */
    protected function resizeAndSaveVideo($fromPath, $toPath, $size)
    {
        $execFromPath = $this->fullPath($fromPath);
        $execToPath   = $this->fullPath($toPath);

        $exec = "/usr/bin/HandBrakeCLI -O -Z \"Fast {$size}\" -i {$execFromPath} -o {$execToPath}";

        exec($exec, $output, $return_var);

        if ($return_var !== 0 || ! Storage::exists($toPath)) {
            $this->deleteFile($toPath);

            return $fromPath;
        }

        return $toPath;
    }

    /**
     * @return string
     */
    public function getResize()
    {
        return Arr::get($this->config, 'resize', '576p25');
    }

    /**
     * @param Mediable|Video $model
     * @param array $sizes
     * @return \Illuminate\Database\Eloquent\Model|Mediable
     * @throws FileManagerException
     */
    public function resize(Mediable $model, $sizes)
    {
        $this->checkOriginal($model->original_path);

        $size = Arr::get($sizes, 'resize', Arr::get($sizes, 0, $this->getResize()));

        $path = $model->path;

        $model->deleteVideo();

        $resultPath = $this->resizeAndSaveVideo($model->original_path, $path, $size);

        if ($resultPath === $model->original_path) {
            $model->update([
                'path' => $model->original_path,
                'url'  => $model->original_url
            ]);
        }

        return $model;
    }

    /**
     * @param Mediable|Video $model
     * @return Mediable
     * @throws FileManagerException
     */
    public function updateFileNames(Mediable $model)
    {
        $path = $this->generateUniquePathFromExisting($model->path);

        $this->renameFile($model->path, $path);

        $url = $this->pathToUrl($path);

        $model->update(compact('path', 'url'));

        return $model;
    }
}
