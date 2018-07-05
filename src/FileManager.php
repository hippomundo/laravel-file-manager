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
        $path          = $this->moveFile($file);

        return compact(
            'path',
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
     * @throws \ReflectionException
     */
    protected function moveFile(UploadedFile $file)
    {
        $path = StorageManager::generateUniquePath($file, $this->mainFolder($file));

        $this->putFileToPath($path, $file);

        return $path;
    }

    /**
     * @param Mediable|File $model
     * @return \Illuminate\Database\Eloquent\Model|Mediable
     */
    public function updateFileNames(Mediable $model)
    {
        $path = StorageManager::generateUniquePath($model->path);

        $this->renameFile($model->path, $path);

        $model->update(compact('path'));

        return $model;
    }
}
