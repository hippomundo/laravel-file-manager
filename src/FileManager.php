<?php

namespace RGilyov\FileManager;

use Illuminate\Http\UploadedFile;
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
     */
    protected function saveFile(UploadedFile $file)
    {
        $type           = $file->getMimeType();
        $file_size      = $file->getClientSize();
        $folder_path    = $this->mainFolder();
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
     */
    protected function moveFile(UploadedFile $file)
    {
        $path = $this->generateUniquePath($file);

        $baseName = $this->baseName($path);
        $fileName = $this->fileName($path);

        $file->move($baseName, $fileName);

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
