<?php

namespace RGilyov\FileManager;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RGilyov\FileManager\Interfaces\Mediable;
use RGilyov\FileManager\Models\File;
use RGilyov\FileManager\Models\Media;
use RGilyov\FileManager\Models\Video;
use \RGilyov\FileManager\Interfaces\Manager as ManagerContract;

/**
 * Class Manager
 * @package RGilyov\FileManager
 */
class Manager implements ManagerContract
{
    /**
     * @var BaseManager
     */
    protected $manager;

    /**
     * Manager constructor.
     * @param BaseManager $manager
     */
    public function __construct(BaseManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param UploadedFile $file
     * @return Media|Video|File
     */
    public function create(UploadedFile $file)
    {
        return $this->resolveMethod(__FUNCTION__, func_get_args());
    }

    /**
     * @param UploadedFile $file
     * @param Mediable $model
     * @return Media|Video|File
     */
    public function update(UploadedFile $file, Mediable $model)
    {
        return $this->resolveMethod(__FUNCTION__, func_get_args());
    }

    /**
     * @param Mediable|Model $model
     * @return bool
     */
    public function delete(Mediable $model)
    {
        return $this->resolveMethod(__FUNCTION__, func_get_args());
    }

    /**
     * @param Mediable $model
     * @param $value
     * @return Media
     */
    public function rotate(Mediable $model, $value)
    {
        return $this->resolveMethod(__FUNCTION__, func_get_args());
    }

    /**
     * @param Mediable $model
     * @param array ...$sizes
     * @return Model
     */
    public function resize(Mediable $model, ...$sizes)
    {
        return $this->resolveMethod(__FUNCTION__, array_merge([$model], $sizes));
    }

    /**
     * @param Mediable $model
     * @return Model|Mediable
     */
    public function updateFileNames(Mediable $model)
    {
        return $this->resolveMethod(__FUNCTION__, func_get_args());
    }

    /**
     * @param $name
     * @param $vars
     * @return mixed|null
     */
    protected function resolveMethod($name, $vars)
    {
        if (method_exists($this->manager, $name)) {
            return call_user_func_array([$this->manager, $name], $vars);
        }

        return null;
    }

    /**
     * @param $check
     * @return Manager
     * @throws FileManagerException
     */
    public static function init($check)
    {
        if (is_string($check)) {
            return new static(static::resolveStringCheck($check));
        }

        if ($check instanceof Mediable) {
            return new static(static::resolveMediableCheck($check));
        }

        if ($check instanceof Relation) {
            return new static(static::resolveMediableCheck($check->getRelated()));
        }

        throw new FileManagerException("Not able to resolve");
    }

    /**
     * @param $string
     * @return FileManager|MediaManager|VideoManager
     * @throws FileManagerException
     */
    protected static function resolveStringCheck($string)
    {
        switch (Str::singular(strtolower($string))) {
            case 'image':
            case 'photo':
            case 'media':
                return new MediaManager();
            case 'video':
                return new VideoManager();
            case 'file':
                return new FileManager();
        }

        throw new FileManagerException("Not able to resolve {$string}");
    }

    /**
     * @param $mediable
     * @return FileManager|MediaManager|VideoManager
     * @throws FileManagerException
     */
    protected static function resolveMediableCheck($mediable)
    {
        if ($mediable instanceof Media) {
            return new MediaManager();
        }

        if ($mediable instanceof Video) {
            return new VideoManager();
        }

        if ($mediable instanceof File) {
            return new FileManager();
        }

        $className = class_basename($mediable);

        throw new FileManagerException("Not able to resolve {$className}");
    }
}
