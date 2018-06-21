<?php

namespace RGilyov\FileManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use RGilyov\FileManager\ManagerFactory;
use RGilyov\FileManager\ResolvedRelation;

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
    public $fileManagerOptions;

    /**
     * MediaTrait constructor.
     * @param array $attributes
     */
    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);

        $this->fileManagerOptions = $this->formatFileManagerOptions();
    }

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
    public function formatFileManagerOptions()
    {
        $formattedOptions = [];

        foreach ($this->fileManagerOptions() as $method => &$options) {
            $methodKey = (is_array($options)) ? $method : $options;

            $formattedOptions[$methodKey] = [
                "request_binding" => Arr::get($options, 'request_binding', $methodKey),
                "config"          => Arr::get($options, 'config', null),
                "data"            => Arr::get($options, 'data', [])
            ];
        }

        return $formattedOptions;
    }

    /**
     * @param array $attributes
     * @return Model|FileManager
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
     * @return \Illuminate\Database\Eloquent\Collection|Model|\RGilyov\FileManager\Interfaces\Mediable
     */
    public function fileManagerFindFile($relationOrId, $id = null)
    {
        return $this->resolveRelation(func_get_args())->find();
    }

    /**
     * @param $relationOrId
     * @param null $id
     * @return bool|null
     * @throws \Exception
     */
    public function fileManagerDeleteFile($relationOrId, $id = null)
    {
        return $this->resolveRelation(func_get_args())->delete();
    }

    /**
     * @param $relationOrId
     * @param null $idOrSizes
     * @param array|null $sizes
     * @return Model|\RGilyov\FileManager\Interfaces\Mediable
     * @throws \RGilyov\FileManager\FileManagerException
     */
    public function fileManagerResize($relationOrId, $idOrSizes = null, array $sizes = null)
    {
        $sizes = is_array($idOrSizes) ? $idOrSizes : $sizes;

        $resolved = $this->resolveRelation(func_get_args());

        $manager = $this->getFileManager($resolved->getRelationName());

        $model = $resolved->find();

        return $manager->resize($model, $sizes);
    }

    /**
     * @param $relationOrId
     * @param null $idOrRotation
     * @param array|null $rotation
     * @return \RGilyov\FileManager\Interfaces\Mediable
     * @throws \RGilyov\FileManager\FileManagerException
     */
    public function fileManagerRotateImage($relationOrId, $idOrRotation = null, $rotation = null)
    {
        $rotation = $rotation ? $rotation : $idOrRotation;

        $resolved = $this->resolveRelation(func_get_args());

        $manager = $this->getFileManager($resolved->getRelationName());

        $model = $resolved->find();

        return $manager->rotate($model, $rotation);
    }

    /**
     * @param $relationOrId
     * @param null $id
     * @return \RGilyov\FileManager\Interfaces\Mediable
     * @throws \RGilyov\FileManager\FileManagerException
     */
    public function fileManagerUpdateNames($relationOrId, $id = null)
    {
        $resolved = $this->resolveRelation(func_get_args());

        $manager = $this->getFileManager($resolved->getRelationName());

        $model = $resolved->find();

        return $manager->updateFileNames($model);
    }

    /**
     * @param array $args
     * @return ResolvedRelation
     */
    protected function resolveRelation(array $args)
    {
        if (count($args) === 1) {
            $relationName = '';
            $id           = isset($args[0]) ? $args[0] : null;
        } else {
            $relationName = $args[0];
            $id           = ( int )$args[1];
        }

        $relation = $this->getFileRelation($relationName);

        return new ResolvedRelation($id, $relation, $relationName);
    }

    /**
     * @param $relation
     * @return mixed
     */
    public function getFileRelation($relation)
    {
        return $this->{$this->getFileRelationMethod($relation)}();
    }

    /**
     * @param $relation
     * @return string
     */
    public function getFileRelationMethod($relation)
    {
        if ($relation && is_string($relation)) {
            if (isset($this->mediaOptions[$relation])) {
                return $relation;
            }

            foreach ($this->fileManagerOptions as $method => $options) {
                if ($relation == $options['request_binding']) {
                    return $method;
                }
            }
        }

        return $this->{array_keys($this->fileManagerOptions)[0]}();
    }

    /**
     * @param array $attributes
     * @return Collection
     * @throws \Exception
     */
    protected function checkRequestFieldsAndCreateOrUpdateFile(array $attributes)
    {
        $created = collect([]);
        foreach ($this->fileManagerOptions as $method => &$options) {
            if (isset($attributes[$options['request_binding']])) {
                $created->offsetSet(
                    $options['request_binding'],
                    $this->createFileAction($attributes[$options['request_binding']], $method)
                );
            }
        }

        return $created;
    }

    /**
     * @param $file
     * @param $method
     * @return File|Media|Video|bool|Collection
     * @throws \Exception
     */
    protected function createFileAction($file, $method)
    {
        if ($this->{$method}() instanceof BelongsToMany) {
            if (is_array($file)) {
                $created = collect([]);
                foreach ($file as $f) {
                    $created->push($this->saveAndAssociateFile($f, $method, 'attach'));
                }
                return $created;
            } else {
                return $this->saveAndAssociateFile($file, $method, 'attach');
            }
        } elseif ($this->{$method}() instanceof BelongsTo) {
            return $this->saveAndAssociateFile($file, $method);
        }

        return false;
    }

    /**
     * @param $file
     * @param $method
     * @return File|bool|Media|Video
     * @throws \Exception
     */
    protected function updateOrSaveFile($file, $method)
    {
        /** @var $mediaModel Model */

        if (($mediaModel = $this->{$method}()->first()) && ResolvedRelation::isMedia($mediaModel)) {
            $mediaModel->delete();
        }

        return $this->saveAndAssociateFile($file, $method, 'associate');
    }

    /**
     * @param UploadedFile $file
     * @param $relation
     * @return $this|Model
     * @throws \RGilyov\FileManager\FileManagerException
     */
    protected function saveFile(UploadedFile $file, $relation)
    {
        $manager = $this->getFileManager($relation);

        return $manager->create($file);
    }

    /**
     * @param $relation
     * @return \RGilyov\FileManager\FileManager|\RGilyov\FileManager\MediaManager|\RGilyov\FileManager\VideoManager
     * @throws \RGilyov\FileManager\FileManagerException
     */
    protected function getFileManager($relation)
    {
        $config = $this->fileManagerOptions[$relation]['config'];

        $manager = ManagerFactory::get($this->{$relation}());

        $manager->setPreFolder($this->fileManagerFolder());

        if ($config) {
            $manager->setConfig($config);
        }

        return $manager;
    }

    /**
     * @param $file
     * @param $method
     * @param string $relationSaveMethod
     * @return $this|bool|Model
     * @throws \RGilyov\FileManager\FileManagerException
     */
    protected function saveAndAssociateFile($file, $method, $relationSaveMethod = 'associate')
    {
        /** @var $result Model */
        if ($file instanceof UploadedFile) {
            $options = $this->fileManagerOptions[$method];

            $fileModel = $this->saveFile($file, $method);

            if (isset($options['data']) && ! empty($options['data'])) {
                $fileModel->fill($options['data']);
            }

            $fileModel->save();

            $result = $this->{$method}()->{$relationSaveMethod}($fileModel);

            if ($relationSaveMethod == 'associate') {
                $result->update();
            }

            return $fileModel;
        }

        return false;
    }
}
