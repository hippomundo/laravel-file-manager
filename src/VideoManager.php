<?php

namespace RGilyov\FileManager;

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
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \ReflectionException
     */
    protected function saveFile(UploadedFile $file)
    {
        $type          = $file->getMimeType();
        $file_size     = $file->getClientSize();
        $folder_path   = $this->mainFolder($file);
        $original_name = StorageManager::originalName($file);
        $storage       = $this->getStorageName();
        $extension     = StorageManager::extension($file);
        $hash          = $this->makeHash($original_name);
        $original_path = $this->moveOriginal($file);
        $path          = $this->saveVideo($original_path);

        return compact(
            'original_path',
            'path',
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
     * @return mixed
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function saveVideo($original_path)
    {
        $path = StorageManager::generateUniquePath($original_path);

        $size = $this->getResize();

        return $this->resizeAndSaveVideo($original_path, $path, $size);
    }

    /**
     * @param $fromPath
     * @param $toPath
     * @param $size
     * @return mixed
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function resizeAndSaveVideo($fromPath, $toPath, $size)
    {
        if (StorageManager::isSingleCloudDisk()) {
            return $this->resizeOnlyCloud($fromPath, $toPath, $size);
        }

        $execFromPath = StorageManager::originalFullPath($fromPath);

        $execToPath = StorageManager::originalFullPath($toPath);

        $this->resizeVideo($execFromPath, $execToPath, $size);

        return $toPath;
    }

    /**
     * @param $fromPath
     * @param $toPath
     * @param $size
     * @return mixed
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function resizeOnlyCloud($fromPath, $toPath, $size)
    {
        return StorageManager::tmpScope($fromPath, function ($tmp) use ($fromPath, $toPath, $size) {

            $tmpToPath = StorageManager::generateTmpPath($toPath);

            $execToPath = StorageManager::tmpFullPath($tmpToPath);

            $result = $this->resizeVideo($tmp, $execToPath, $size);

            if ($result !== 0) {
                $this->putFileToPath($toPath, StorageManager::get($fromPath));
            } else {
                $this->putFileToPath($toPath, StorageManager::getTmpDisk()->get($tmpToPath));

                StorageManager::deleteTmpFile($tmpToPath);
            }

            return $toPath;
        });
    }

    /**
     * @param $fromPath
     * @param $toPath
     * @param $size
     * @return mixed
     */
    protected function resizeVideo($fromPath, $toPath, $size)
    {
        $exec = "/usr/bin/HandBrakeCLI -O -Z \"Fast {$size}\" -i {$fromPath} -o {$toPath}";

        exec($exec, $output, $return_var);

        return $return_var;
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
     * @throws Exceptions\FileManagerException
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function resize(Mediable $model, $sizes)
    {
        $this->checkOriginal($model->original_path);

        $size = Arr::get($sizes, 'resize', Arr::get($sizes, 0, $this->getResize()));

        $path = $model->path;

        $model->deleteVideo();

        $this->resizeAndSaveVideo($model->original_path, $path, $size);

        return $model;
    }

    /**
     * @param Mediable|Video $model
     * @return \Illuminate\Database\Eloquent\Model|Mediable
     */
    public function updateFileNames(Mediable $model)
    {
        $path = StorageManager::generateUniquePath($model->path);

        $this->renameFile($model->path, $path);

        $model->update(compact('path', 'url'));

        return $model;
    }
}
