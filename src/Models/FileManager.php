<?php

namespace RGilyov\FileManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use RGilyov\FileManager\ManagerFactory;
use RGilyov\FileManager\ResolvedRelation;

/**
 * Class MediaTrait|Model
 * @package App\EloquentTraits
 */
trait MediaTrait
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
     * Media config
     *
     * @return array
     */
    public function fileManagerConfig()
    {
        return null;
    }

    /**
     * @param Model $model
     * @return mixed
     */
    public function fileManagerFolder($model)
    {
        return $model->id;
    }

    /**
     * @return array
     */
    public function fileManagerOptions()
    {
        return [
            'file' => [
                'request_binding' => 'file',
                'data' => []
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
            $newMethodKey = (is_array($options)) ? $method : $options;
            $formattedOptions[$newMethodKey] = [
                "request_binding" => Arr::get($options, 'request_binding', $newMethodKey),
                "data"            => Arr::get($options, 'data', [])
            ];
        }

        return $formattedOptions;
    }

    /**
     * @param array $attributes
     * @return MediaTrait|Model
     * @throws \Exception
     */
    public static function create(array $attributes = [])
    {
        /** @var $model Model|MediaTrait */
        $model = new static($attributes);
        $model->save();

        $model->checkRequestFieldsAndCreateOrUpdateFile($model, $attributes);

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
        /** @var $this Model|MediaTrait */
        if (!$this->exists) {
            return false;
        }

        $this->checkRequestFieldsAndCreateOrUpdateFile($this, $attributes);

        return parent::update($attributes, $options);
    }

    /**
     * @param array $attributes
     * @return bool|Collection
     * @throws \Exception
     */
    public function fileManagerSaveFiles(array $attributes)
    {
        /** @var $this Model|MediaTrait */
        if (!$this->exists) {
            return false;
        }

        return $this->checkRequestFieldsAndCreateOrUpdateFile($this, $attributes);
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

        $manager = $this->getFileManager($resolved->getRelation(), $this->fileManagerFolder($this));

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

        $manager = $this->getFileManager($resolved->getRelation(), $this->fileManagerFolder($this));

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

        $manager = $this->getFileManager($resolved->getRelation(), $this->fileManagerFolder($this));

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
            $relation = '';
            $id       = isset($args[0]) ? $args[0] : null;
        } else {
            $relation = $args[0];
            $id       = ( int )$args[1];
        }

        $relation = $this->getFileRelation($relation);

        return new ResolvedRelation($id, $relation);
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
     * @param Model $model
     * @param array $attributes
     * @return Collection
     * @throws \Exception
     */
    protected function checkRequestFieldsAndCreateOrUpdateFile(Model $model, array $attributes)
    {
        $created = collect([]);
        foreach ($this->fileManagerOptions as $method => &$options) {
            if (isset($attributes[$options['request_binding']])) {
                $created->offsetSet(
                    $options['request_binding'],
                    $this->createFileAction($model, $attributes[$options['request_binding']], $method)
                );
            }
        }

        return $created;
    }

    /**
     * @param $model
     * @param $file
     * @param $method
     * @return File|Media|Video|bool|Collection
     * @throws \Exception
     */
    protected function createFileAction($model, $file, $method)
    {
        if ($model->{$method}() instanceof BelongsToMany) {
            if (is_array($file)) {
                $created = collect([]);
                foreach ($file as $f) {
                    $created->push($this->saveAndAssociateFile($model, $f, $method, 'attach'));
                }
                return $created;
            } else {
                return $this->saveAndAssociateFile($model, $file, $method, 'attach');
            }
        } elseif ($model->{$method}() instanceof BelongsTo) {
            return $this->saveAndAssociateFile($model, $file, $method);
        }

        return false;
    }

    /**
     * @param $model
     * @param $file
     * @param $method
     * @return File|bool|Media|Video
     * @throws \Exception
     */
    protected function updateOrSaveFile($model, $file, $method)
    {
        /** @var $mediaModel Model */
        /** @var $model Model|MediaTrait */
        if (($mediaModel = $model->{$method}()->first()) && ResolvedRelation::isMedia($mediaModel)) {
            $mediaModel->delete();
        }

        return $model->saveAndAssociateFile($model, $file, $method, 'associate');
    }

    /**
     * @param UploadedFile $file
     * @param $relation
     * @param $folder
     * @return $this|Model
     * @throws \RGilyov\FileManager\FileManagerException
     */
    protected function saveFile(UploadedFile $file, $relation, $folder)
    {
        $manager = $this->getFileManager($relation, $folder);

        return $manager->create($file);
    }

    /**
     * @param $relation
     * @param $folder
     * @return \RGilyov\FileManager\FileManager|\RGilyov\FileManager\MediaManager|\RGilyov\FileManager\VideoManager
     * @throws \RGilyov\FileManager\FileManagerException
     */
    protected function getFileManager($relation, $folder)
    {
        $config = $this->fileManagerConfig();

        $manager = ManagerFactory::get($relation);

        $manager->setPreFolder($folder);

        if ($config) {
            $manager->setConfig($config);
        }

        return $manager;
    }

    /**
     * @param $model
     * @param $file
     * @param $method
     * @param string $relationSaveMethod
     * @return $this|bool|Model
     * @throws \RGilyov\FileManager\FileManagerException
     */
    protected function saveAndAssociateFile($model, $file, $method, $relationSaveMethod = 'associate')
    {
        /** @var $result Model */
        if ($file instanceof UploadedFile) {
            $options = $this->fileManagerOptions[$method];

            $relation = $this->{$method}();

            $fileModel = $this->saveFile($file, $relation, $this->fileManagerFolder($model));

            if (isset($options['data']) && !empty($options['data'])) {
                $fileModel->fill($options['data']);
            }

            $fileModel->save();

            $result = $model->{$method}()->{$relationSaveMethod}($fileModel);

            if ($relationSaveMethod == 'associate') {
                $result->update();
            }

            return $fileModel;
        }

        return false;
    }
}
