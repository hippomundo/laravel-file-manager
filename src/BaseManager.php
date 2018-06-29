<?php

namespace RGilyov\FileManager;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use RGilyov\FileManager\Interfaces\Mediable;
use \RGilyov\FileManager\Interfaces\Manager as ManagerContract;

/**
 * Class BaseManager
 * @package RGilyov\FileManager
 */
abstract class BaseManager implements ManagerContract
{
    /**
     * @var string
     */
    protected $preFolder = null;

    /**
     * @var string
     */
    protected $mainFolder;

    /**
     * @var string
     */
    protected $loadedMainFolder;

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var string
     */
    protected $sep;

    /**
     * @var Model|Mediable|Builder
     */
    protected $model;

    /**
     * @var string
     */
    protected $tmpDirectory = 'tmp';

    /**
     * @var array
     */
    protected $storage = [];

    /**
     * FileManager constructor.
     */
    public function __construct()
    {
        $this->model = $this->initModel();

        $this->config = $this->defaultConfig();

        $this->sep = DIRECTORY_SEPARATOR;

        $this->mainFolder = config('file-manager.folder') ?: 'files';
    }

    /**
     * @return Model
     */
    abstract public function initModel();

    /**
     * @return array
     */
    abstract public function defaultConfig();

    /**
     * @param array $config
     * @return $this
     */
    public function setConfig(array $config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * @param string $folderName
     * @return $this
     */
    public function setPreFolder($folderName)
    {
        $this->preFolder = $folderName;

        return $this;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return string
     */
    public function getPreFolder()
    {
        return $this->preFolder;
    }

    /**
     * @param $path
     * @return mixed
     */
    public function pathToUrl($path)
    {
        return str_replace($this->sep, '/', $path);
    }

    /**
     * @param $fileName
     * @param null $increment
     * @return string
     */
    public function makeHash($fileName, $increment = null)
    {
        $index = (is_integer($increment)) ? $increment . '-' : '';

        $hash  = Arr::get($this->config, 'directory') . '/' .
            ($this->preFolder ? $this->preFolder . '/' : '') .
            $index . $fileName;

        if ($this->model->where('hash', $hash)->first()) {
            $increment = (is_null($increment)) ? 2 : ++$increment;
            return $this->makeHash($fileName, $increment);
        }

        return $hash;
    }

    /**
     * @return string
     */
    public function getStorageName()
    {
        return FileManagerHelpers::diskName();
    }

    /**
     * @param UploadedFile $file
     * @return string
     * @throws FileManagerException
     */
    public function moveOriginal(UploadedFile $file)
    {
        $path = $this->mainFolder($file);

        $name = $this->originalName($file);

        $path = FileManagerHelpers::glueParts($path, $name);

        $this->putFileToPath($path, $file);

        return $path;
    }

    /**
     * @param $path
     * @param $contents
     * @throws FileManagerException
     */
    public function putFileToPath($path, $contents)
    {
        $contents = $contents instanceof UploadedFile ? File::get($contents) : $contents;

        if (! Storage::put($path, $contents)) {
            throw new FileManagerException('Was not able to save image');
        }
    }

    /**
     * @param UploadedFile $file
     * @return string
     */
    public function originalName(UploadedFile $file)
    {
        return Str::slug(
            pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), '_'
        ) . "." . $this->extension($file);
    }

    /**
     * @param $path
     * @return string
     */
    public function fileName($path)
    {
        if ($path instanceof UploadedFile) {
            $path = $this->originalName($path);
        }

        return pathinfo($path, PATHINFO_FILENAME);
    }

    /**
     * @param $path
     * @return string
     */
    public function baseName($path)
    {
        if ($path instanceof UploadedFile) {
            $path = $this->originalName($path);
        }

        return pathinfo($path, PATHINFO_BASENAME);
    }

    /**
     * @param $path
     * @return string
     */
    public function dirName($path)
    {
        if ($path instanceof UploadedFile) {
            $path = $this->originalName($path);
        }

        return pathinfo($path, PATHINFO_DIRNAME);
    }

    /**
     * @param $path
     * @return mixed
     */
    public function extension($path)
    {
        if ($path instanceof UploadedFile) {
            $path = $path->getClientOriginalName();
        }

        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * @param $file
     * @param null $index
     * @param bool $skipCheck
     * @return string
     */
    public function mainFolder($file, $index = null, $skipCheck = false)
    {
        if ($this->loadedMainFolder) {
            return $this->loadedMainFolder;
        }

        $dir = $this->mainFolder . $this->sep
            . Arr::get($this->config, 'directory') . $this->sep
            . ($this->preFolder ? $this->preFolder . $this->sep : "")
            . $this->fileName($file) . ($index ? "_{$index}" : "");

        if (! $skipCheck && StorageManager::exists($dir)) {
            return $this->mainFolder($file, ++$index);
        }

        return $this->loadedMainFolder = $dir;
    }

    /**
     * @param $file
     * @return string
     */
    public function generateUniquePath($file)
    {
        $name = Str::slug(Str::random(), '_') . "." . $this->extension($file);

        $path = FileManagerHelpers::glueParts($this->mainFolder($file), $name);

        if (StorageManager::exists($path)) {
            return $this->generateUniquePath($file);
        }

        return $path;
    }

    /**
     * @param $path
     * @return string
     */
    public function generateUniquePathFromExisting($path)
    {
        $baseName  = $this->dirName($path);

        $extension = $this->extension($path);

        $name = Str::slug(Str::random(), '_') . "." . $extension;

        $uniquePath = FileManagerHelpers::glueParts($baseName, $name);

        if (StorageManager::exists($uniquePath)) {
            return $this->generateUniquePathFromExisting($uniquePath);
        }

        return $uniquePath;
    }

    /**
     * @param $path
     * @param $newPath
     * @return mixed
     * @throws FileManagerException
     */
    public function renameFile($path, $newPath)
    {
        if (StorageManager::exists($path)) {
            return Storage::move($path, $newPath);
        }

        throw new FileManagerException('Was not able to rename the file, it does not exists.');
    }

    /**
     * @param $path
     * @throws FileManagerException
     */
    public function checkOriginal($path)
    {
        if (! StorageManager::exists($path)) {
            throw new FileManagerException('Original file does not exists');
        }
    }

    /**
     * @param $path
     * @return string
     */
    public function localFullPath($path)
    {
        return FileManagerHelpers::glueParts($this->localFullPathPrefix(), $path);
    }

    /**
     * @param $path
     * @return string
     */
    public function removePrefixFromLocalPath($path)
    {
        return ltrim(str_replace($this->localFullPathPrefix(), '', $path), $this->sep);
    }

    /**
     * @return string
     */
    public function localFullPathPrefix()
    {
        $storage = FileManagerHelpers::isCloud() ? Storage::disk('local') : Storage::disk();

        return $storage->getDriver()->getAdapter()->getPathPrefix();
    }

    /**
     * @param $file
     * @return string
     */
    protected function makeTmpFile($file)
    {
        if ($file instanceof UploadedFile) {
            return $file;
        }

        if (FileManagerHelpers::isCloud()) {
            $contents = Storage::get($file);

            $tmpPath = $this->generateTmpPath($file);

            Storage::disk('local')->put($tmpPath, $contents);

            return $this->localFullPath($tmpPath);
        }

        return $this->localFullPath($file);
    }

    /**
     * @param $path
     * @return string
     */
    public function generateTmpPath($path)
    {
        if (FileManagerHelpers::isCloud()) {
            return $this->generateUniquePathFromExisting(
                FileManagerHelpers::glueParts($this->tmpDirectory, $this->baseName($path))
            );
        }

        return $path;
    }

    /**
     * @param $file
     * @return bool
     */
    protected function deleteTmpFile($file)
    {
        if (! is_string($file)) {
            return false;
        }

        $file = $this->removePrefixFromLocalPath($file);

        if (strpos($file, $this->tmpDirectory) === 0) {
            if (Storage::disk('local')->exists($file)) {
                return Storage::disk('local')->delete($file);
            }
        }

        return false;
    }

    /**
     * @param $path
     * @return bool
     */
    public function deleteFile($path)
    {
        if (StorageManager::exists($path)) {
            return Storage::delete($path);
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Model management
    |--------------------------------------------------------------------------
    */

    /**
     * @param Mediable|Model $model
     * @return mixed
     * @throws \Exception
     */
    public function delete(Mediable $model)
    {
        $model->deleteFile();

        $dirName = $this->dirName($model->path);

        if (StorageManager::exists($dirName)) {
            Storage::deleteDirectory($dirName);
        }

        return $model->delete();
    }

    /**
     * @param Mediable $model
     * @param $value
     * @return Mediable
     */
    public function rotate(Mediable $model, $value)
    {
        return $model;
    }

    /**
     * @param Mediable $model
     * @param array $sizes
     * @return Model|Mediable
     */
    public function resize(Mediable $model, $sizes)
    {
        return $model;
    }

    /**
     * @param UploadedFile $file
     * @return $this|\Illuminate\Database\Eloquent\Model
     * @throws FileManagerException
     */
    public function create(UploadedFile $file)
    {
        return $this->model->create($this->saveFile($file));
    }

    /**
     * @param UploadedFile $file
     * @return array
     * @throws FileManagerException
     */
    abstract protected function saveFile(UploadedFile $file);

    /**
     * @param UploadedFile $file
     * @param Mediable|Model $model
     * @return bool
     * @throws FileManagerException
     */
    public function update(UploadedFile $file, Mediable $model)
    {
        $model->deleteFile();

        return $model->update($this->saveFile($file));
    }
}
