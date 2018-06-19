<?php

namespace RGilyov\FileManager;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use RGilyov\FileManager\Interfaces\Mediable;

/**
 * Class BaseManager
 * @package RGilyov\FileManager
 */
abstract class BaseManager
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
     * @var array
     */
    protected $config = [];

    /**
     * @var string
     */
    protected $sep;

    /**
     * @var Model|Mediable
     */
    protected $model;

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
     * @param $folderName
     * @return $this
     */
    public function setPreFolder($folderName)
    {
        $this->preFolder = is_string($folderName) ? $folderName : '';

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
        return strtolower(class_basename(Storage::getDriver()));
    }

    /**
     * @param UploadedFile $file
     * @return string
     */
    public function moveOriginal(UploadedFile $file)
    {
        $path = $this->mainFolder();
        $name = $this->originalName($file);

        $file->move($path, $name);

        return $this->glueDirParts($path, $name);
    }

    /**
     * @param UploadedFile $file
     * @return string
     */
    public function originalName(UploadedFile $file)
    {
        return Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), '_');
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
     * @return mixed
     */
    public function extension($path)
    {
        if ($path instanceof UploadedFile) {
            return $path->extension();
        }

        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * @return string
     */
    public function mainFolder()
    {
        return $this->mainFolder . $this->sep
            . Arr::get($this->config, 'directory')
            . ($this->preFolder ? $this->sep . $this->preFolder : '');
    }

    /**
     * @param $part1
     * @param $part2
     * @return string
     */
    public function glueDirParts($part1, $part2)
    {
        return rtrim($part1, $this->sep) . $this->sep . ltrim($part2, $this->sep);
    }

    /**
     * @param $file
     * @return string
     */
    public function generateUniquePath($file)
    {
        $name = Str::slug(Str::random(), '_') . "." . $this->extension($file);

        $path = $this->glueDirParts($this->mainFolder(), $name);

        if (Storage::exists($path)) {
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
        $baseName  = $this->baseName($path);
        $extension = $this->extension($path);

        $name = Str::slug(Str::random(), '_') . "." . $extension;

        $uniquePath = $this->glueDirParts($baseName, $name);

        if (Storage::exists($uniquePath)) {
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
        if (Storage::exists($path)) {
            return Storage::move($path, $newPath);
        }

        throw new FileManagerException('Was not able to rename the file, it does not exists.');
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

    /**
     * @param Mediable|Model $model
     * @return mixed
     * @throws \Exception
     */
    public function delete(Mediable $model)
    {
        $model->deleteFile();

        return $model->delete();
    }
}
