<?php

namespace Hippomundo\FileManager\Models;

use Illuminate\Database\Eloquent\Builder;
use \Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Hippomundo\FileManager\Exceptions\FileManagerException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Hippomundo\FileManager\ManagerFactory;
use Hippomundo\FileManager\ResolvedRelation;

/**
 * Class MediaTrait|Model
 * @package App\EloquentTraits
 */
trait FileManager
{
    /**
     * Key is relation method, value is request field
     *
     * @var array
     */
    protected static $fileManagerOptions;

    /**
     * @return mixed
     */
    public function fileManagerFolder()
    {
        return $this->id;
    }

    /**
     * @return array
     */
    public function fileManagerOptions()
    {
        return [
            'file' => [
                'request_binding' => 'file',
                'config'          => null,
                'data'            => [],
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getFileManagerOptions()
    {
        if (! is_null(static::$fileManagerOptions)) {
            return static::$fileManagerOptions;
        }

        return static::$fileManagerOptions = $this->formatFileManagerOptions();
    }

    /**
     * @return array
     */
    protected function formatFileManagerOptions()
    {
        $formattedOptions = [];

        foreach ($this->fileManagerOptions() as $method => &$options) {
            $methodKey = (is_array($options)) ? $method : $options;

            $formattedOptions[$methodKey] = [
                "request_binding" => Arr::get($options, 'request_binding', $methodKey),
                "config"          => Arr::get($options, 'config', []),
                "data"            => Arr::get($options, 'data', [])
            ];
        }

        return $formattedOptions;
    }

    /**
     * @param array $config
     * @param $methodKey
     * @return array
     */
    protected function checkConfig(array $config, $methodKey)
    {
        if (! isset($config['directory'])) {
            $config['directory'] = Str::snake($methodKey);
        }

        return $config;
    }

    /**
     * @param array $attributes
     * @return Model|FileManager
     * @throws FileManagerException
     * @throws \Exception
     */
    public static function create(array $attributes = [])
    {
        /** @var $model Model|FileManager */

        $model = new static($attributes);

        $model->save();

        $model->checkRequestFieldsAndCreateOrUpdateFile($attributes);

        return $model;
    }

    /**
     * @param array $attributes
     * @param array $options
     * @return bool
     * @throws FileManagerException
     * @throws \Exception
     */
    public function update(array $attributes = [], array $options = [])
    {
        /** @var $this Model|FileManager */

        if (! $this->exists) {
            return false;
        }

        $this->checkRequestFieldsAndCreateOrUpdateFile($attributes);

        return parent::update($attributes, $options);
    }

    /**
     * @param array $attributes
     * @return bool|Collection
     * @throws FileManagerException
     * @throws \Exception
     */
    public function fileManagerSaveFiles(array $attributes)
    {
        /** @var $this Model|FileManager */

        if (! $this->exists) {
            return false;
        }

        return $this->checkRequestFieldsAndCreateOrUpdateFile($attributes);
    }

    /**
     * @param $relationOrId
     * @param null $id
     * @return Collection
     * @throws FileManagerException
     */
    public function fileManagerFindFile($relationOrId, $id = null)
    {
        return $this->resolveRelation(['relation' => $relationOrId, 'id' => $id])->find();
    }

    /**
     * @param $relationOrId
     * @param null $id
     * @return bool|null
     * @throws \Exception
     */
    public function fileManagerDeleteFile($relationOrId, $id = null)
    {
        return $this->resolveRelation(['relation' => $relationOrId, 'id' => $id])->delete();
    }

    /**
     * @param $relationOrId
     * @param null $idOrSizes
     * @param array|null $sizes
     * @return Collection
     * @throws FileManagerException
     */
    public function fileManagerResize($relationOrId, $idOrSizes = null, array $sizes = null)
    {
        $sizes = is_array($idOrSizes) ? $idOrSizes : $sizes;

        $resolved = $this->resolveRelation(['relation' => $relationOrId, 'id' => $idOrSizes]);

        return $resolved->resize($sizes);
    }

    /**
     * @param $relationOrId
     * @param null $idOrRotation
     * @param null $rotation
     * @return Collection
     * @throws FileManagerException
     */
    public function fileManagerRotateImage($relationOrId, $idOrRotation = null, $rotation = null)
    {
        $rotation = $rotation ? $rotation : $idOrRotation;

        $resolved = $this->resolveRelation(['relation' => $relationOrId, 'id' => $idOrRotation]);

        return $resolved->rotate($rotation);
    }

    /**
     * @param $relationOrId
     * @param null $id
     * @return Collection
     * @throws FileManagerException
     */
    public function fileManagerUpdateNames($relationOrId, $id = null)
    {
        $resolved = $this->resolveRelation(['relation' => $relationOrId, 'id' => $id]);

        return $resolved->updateFileNames();
    }

    /**
     * @param array $args
     * @return ResolvedRelation
     * @throws FileManagerException
     */
    protected function resolveRelation(array $args)
    {
        $id   = (is_numeric($args['relation']) || is_array($args['relation'])) ? $args['relation'] : $args['id'];
        $name = is_string($args['relation']) ? $args['relation'] : '';

        $method   = $this->getFileRelationMethod($name);
        $relation = $this->getRelationFromMethod($method);
        $manager  = $this->getFileManager($method);

        return new ResolvedRelation($id, $relation, $method, $manager);
    }

    /**
     * @param $method
     * @return mixed
     * @throws FileManagerException
     */
    public function getRelationFromMethod($method)
    {
        $possibleNames = $this->getArrayOfPossibleNames($method);

        foreach ($possibleNames as $relation) {
            if (method_exists($this, $relation)) {
                return $this->{$relation}();
            }
        }

        throw new FileManagerException("The '{$method}' relation does not exists in the model.");
    }

    /**
     * @param $name
     * @return array
     */
    protected function getArrayOfPossibleNames($name)
    {
        return [$name, Str::snake($name), Str::camel($name), Str::studly($name)];
    }

    /**
     * @param $relation
     * @return string
     */
    public function getFileRelationMethod($relation)
    {
        $options = $this->getFileManagerOptions();

        if ($relation && is_string($relation)) {
            if (isset($options[$relation])) {
                return $relation;
            }

            foreach ($options as $method => $values) {
                if ($relation === $values['request_binding']) {
                    return $method;
                }
            }
        }

        return array_keys($options)[0];
    }

    /**
     * @param array $attributes
     * @return Collection
     * @throws FileManagerException
     * @throws \Exception
     */
    protected function checkRequestFieldsAndCreateOrUpdateFile(array $attributes)
    {
        $created = collect([]);

        foreach ($this->getFileManagerOptions() as $method => &$options) {

            $file = isset($attributes[$options['request_binding']]) ? $attributes[$options['request_binding']] : null;

            if ($file instanceof UploadedFile || is_array($file)) {
                $this->deleteOldFileIfExists($method);

                $created->offsetSet($options['request_binding'], $this->createFileAction($file, $method));
            }
        }

        return $created;
    }

    /**
     * @param $method
     * @return bool|mixed
     * @throws FileManagerException
     * @throws \Exception
     */
    protected function deleteOldFileIfExists($method)
    {
        /** @var $relation BelongsTo|Relation|Builder|QueryBuilder */
        $relation = $this->getRelationFromMethod($method);

        if ($relation instanceof BelongsTo && $relation->exists()) {
            $model = $relation->first();

            $relation->dissociate()->save();

            return $this->getFileManager($method)->delete($model);
        }

        return false;
    }

    /**
     * @param $file
     * @param $method
     * @return bool|Collection
     * @throws FileManagerException
     * @throws \Exception
     */
    protected function createFileAction($file, $method)
    {
        if (! method_exists($this, $method)) {
            return false;
        }

        $created = collect([]);

        $file = is_array($file) ? $file : [$file];

        foreach ($file as $f) {
            $created->push($this->saveAndAssociateFile($f, $method));
        }

        return $created;
    }

    /**
     * @param $relation
     * @return \Hippomundo\FileManager\FileManager|\Hippomundo\FileManager\MediaManager|\Hippomundo\FileManager\VideoManager
     * @throws FileManagerException
     */
    protected function getFileManager($relation)
    {
        $options = $this->getFileManagerOptions();

        $config = $options[$relation]['config'];

        $manager = ManagerFactory::get($this->getRelationFromMethod($relation));

        $manager->setPreFolder($this->fileManagerFolder());

        if ($config) {
            $manager->setConfig($config);
        }

        return $manager;
    }

    /**
     * @param $file
     * @param $method
     * @return bool|mixed
     * @throws FileManagerException
     * @throws \Exception
     */
    protected function saveAndAssociateFile($file, $method)
    {
        /** @var $result Model */
        if ($file instanceof UploadedFile) {
            $resolved = $this->resolveRelation(['id' => 0, 'relation' => $method]);

            return $resolved->save($file, Arr::get($this->getFileManagerOptions(), "{$method}.data"));
        }

        return false;
    }
}
