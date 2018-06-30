<?php

namespace RGilyov\FileManager;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Illuminate\Support\Arr;
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
     */
    public function moveOriginal(UploadedFile $file)
    {
        $path = $this->mainFolder($file);

        $name = StorageManager::originalName($file);

        $path = FileManagerHelpers::glueParts($path, $name);

        $this->putFileToPath($path, $file);

        return $path;
    }

    /**
     * @param $path
     * @param $contents
     */
    public function putFileToPath($path, $contents)
    {
        $contents = $contents instanceof UploadedFile ? File::get($contents) : $contents;

        StorageManager::put($path, $contents);
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
            . StorageManager::fileName($file) . ($index ? "_{$index}" : "");

        if (! $skipCheck && StorageManager::exists($dir)) {
            return $this->mainFolder($file, ++$index);
        }

        return $this->loadedMainFolder = $dir;
    }

    /**
     * @param $path
     * @param $newPath
     * @return mixed
     * @throws FileManagerException
     */
    public function renameFile($path, $newPath)
    {
        StorageManager::move($path, $newPath);

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

        $dirName = StorageManager::dirName($model->path);

        StorageManager::deleteDirectory($dirName);

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
