<?php

namespace RGilyov\FileManager;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use RGilyov\FileManager\Interfaces\Mediable;
use RGilyov\FileManager\Models\File;
use RGilyov\FileManager\Models\Media;
use RGilyov\FileManager\Models\Video;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class ResolvedRelation
 * @package RGilyov\FileManager
 */
class ResolvedRelation
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var Relation
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
     * @return \Illuminate\Database\Eloquent\Collection|Model|Mediable
     */
    public function find()
    {
        if ($this->relation instanceof BelongsToMany) {
            return $this->relation->find($this->id);
        } elseif ($this->relation instanceof BelongsTo) {
            return $this->relation->first();
        }

        return null;
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
     * @return Model|Mediable
     */
    public function resize($sizes)
    {
        $model = $this->find();

        return $this->manager->resize($model, $sizes);
    }

    /**
     * @param $rotation
     * @return Mediable
     */
    public function rotate($rotation)
    {
        $model = $this->find();

        return $this->manager->rotate($model, $rotation);
    }

    /**
     * @return Model|Mediable
     */
    public function updateFileNames()
    {
        $model = $this->find();

        return $this->manager->updateFileNames($model);
    }

    /**
     * @return bool|null
     * @throws \Exception
     */
    public function delete()
    {
        $model = $this->find();

        if ($this->isMedia($model)) {
            if ($this->relation instanceof BelongsToMany) {
                $this->relation->detach($model->id);
            } elseif($this->relation instanceof BelongsTo) {
                $this->relation->dissociate()->save();
            }

            return $this->manager->delete($model);
        }

        return false;
    }

    /**
     * @param Mediable|Model $model
     * @return bool|mixed
     * @throws \Exception
     */
    public function associate(Mediable $model)
    {
        if ($this->isMedia($model)) {
            if ($this->relation instanceof BelongsToMany) {
                $this->relation->attach($model->id);
            } elseif($this->relation instanceof BelongsTo) {
                $this->relation->associate($model)->save();
            }

            return $model;
        }

        return false;
    }

    /**
     * @param Model $model
     * @return bool
     */
    public static function isMedia(Model $model)
    {
        return ($model instanceof Media || $model instanceof Video || $model instanceof File);
    }
}