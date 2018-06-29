<?php

namespace RGilyov\FileManager;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use RGilyov\FileManager\Interfaces\Mediable;
use RGilyov\FileManager\Models\File;

/**
 * Class FileManager
 * @package RGilyov\FileManager
 */
class FileManager extends BaseManager
{
    /**
     * @return mixed
     */
    public function defaultConfig()
    {
        return config('file-manager.files.default');
    }

    /**
     * @return File
     */
    public function initModel()
    {
        return new File();
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
        $path           = $this->moveFile($file);
        $url            = $this->pathToUrl($path);

        return compact(
            'path',
            'url',
            "file_size",
            'folder_path',
            'original_name',
            'extension',
            'hash',
            'storage',
            'type'
        );
    }

    /**
     * @param UploadedFile $file
     * @return string
     * @throws FileManagerException
     */
    protected function moveFile(UploadedFile $file)
    {
        $path = $this->generateUniquePath($file);

        $this->putFileToPath($path, $file);

        return $path;
    }

    /**
     * @param Mediable|File $model
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
