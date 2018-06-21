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
     * ResolvedRelation constructor.
     * @param $relation
     * @param $id
     */
    public function __construct($id, $relation, $relationName)
    {
        $this->relation = $relation;

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

            return $model->delete();
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