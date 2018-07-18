<?php

namespace RGilyov\FileManager;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use RGilyov\FileManager\Exceptions\FileManagerException;
use RGilyov\FileManager\Interfaces\Mediable;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class ResolvedRelation
 * @package RGilyov\FileManager
 */
class ResolvedRelation
{
    /**
     * @var int|array
     */
    protected $id;

    /**
     * @var Relation|BelongsTo|BelongsToMany
     */
    protected $relation;

    /**
     * @var string
     */
    protected $relationName;

    /**
     * @var BaseManager
     */
    protected $manager;

    /**
     * ResolvedRelation constructor.
     * @param $id
     * @param $relation
     * @param $relationName
     * @param BaseManager $manager
     */
    public function __construct($id, $relation, $relationName, BaseManager $manager)
    {
        $this->relation = $relation;

        $this->manager = $manager;

        $this->relationName = $relationName;

        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getRelationName()
    {
        return $this->relationName;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Relation
     */
    public function getRelation()
    {
        return $this->relation;
    }

    /**
     * @return BaseManager
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * @return Collection
     */
    public function find()
    {
        $result = collect([]);

        if ($this->relation instanceof BelongsToMany) {
            if (! $this->id || is_array($this->id)) {

                $result = $this->relation->get();

                if (is_array($this->id)) {
                    $result = $result->filter(function (Model $model) {
                        return in_array($model->id, $this->id);
                    });
                }
            } elseif($this->id) {
                $result = collect([$this->relation->find($this->id)]);
            }
        } elseif ($this->relation instanceof BelongsTo) {
            $result = collect([$this->relation->first()]);
        }

        return $result->filter(function ($model) {
            return $model instanceof Model;
        });
    }

    /**
     * @param UploadedFile $file
     * @param array $data
     * @return bool|mixed
     * @throws FileManagerException
     * @throws \Exception
     */
    public function save(UploadedFile $file, array $data = [])
    {
        $model = $this->manager->create($file);

        if (empty($data)) {
            $model->update($data);
        }

        return $this->associate($model);
    }

    /**
     * @param $sizes
     * @return Collection
     */
    public function resize($sizes)
    {
        return $this->find()->transform(function (Model $model) use ($sizes) {
            /** @var $model Mediable */
            return $this->manager->resize($model, $sizes);
        });
    }

    /**
     * @param $rotation
     * @return Collection
     */
    public function rotate($rotation)
    {
        return $this->find()->transform(function (Model $model) use ($rotation) {
            /** @var $model Mediable */
            return $this->manager->rotate($model, $rotation);
        });
    }

    /**
     * @return Collection
     */
    public function updateFileNames()
    {
        return $this->find()->transform(function (Model $model) {
            /** @var $model Mediable */
            return $this->manager->updateFileNames($model);
        });
    }

    /**
     * @return bool
     */
    public function delete()
    {
        return $this->find()->transform(function (Model $model) {
            if ($this->relation instanceof BelongsToMany) {
                $this->relation->detach($model->id);
            } elseif($this->relation instanceof BelongsTo) {
                $this->relation->dissociate()->save();
            }

            return $this->manager->delete($model);
        })->reduce(function ($carry, $item) {
            return $carry && $item;
        }, true);
    }

    /**
     * @param Mediable|Model $model
     * @return bool|mixed
     * @throws \Exception
     */
    public function associate(Mediable $model)
    {
        if (FileManagerHelpers::isMedia($model)) {
            if ($this->relation instanceof BelongsToMany) {
                $this->relation->attach($model->id);
            } elseif($this->relation instanceof BelongsTo) {
                $this->relation->associate($model)->save();
            }

            return $model;
        }

        return false;
    }
}
