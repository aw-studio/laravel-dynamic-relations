<?php

namespace AwStudio\DynamicRelations;

use BadMethodCallException;
use Illuminate\Database\Eloquent\InvalidCastException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use LogicException;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 * @property-read \Illuminate\Database\Eloquent\Collection $dynamicRelations
 */
trait HasDynamicRelations
{
    /**
     * Get dynamic relations.
     *
     * @return void
     */
    public function dynamicRelations()
    {
        return $this->morphMany($this->getDynamicRelationModel(), 'model');
    }

    /**
     * Attach one or more models as a dynamic relationship.
     *
     * @param  mixed            $relation
     * @param  Model|Collection $models
     * @return void
     *
     * @throws InvalidCastException
     * @throws LogicException
     * @throws InvalidArgumentException
     */
    public function attach($relation, Model | Collection $models)
    {
        $isMany = $models instanceof Collection;

        if ($models instanceof Model) {
            $models = new Collection([$models]);
        }

        foreach ($models as $model) {
            $bridge = $this->newDynamicRelationModel($model);
            $bridge->is_many = $isMany;
            $bridge->relation = $relation;
            $bridge->save();

            $this->setDynamicRelationResolver($bridge);
        }
    }

    /**
     * Detach a relation model.
     *
     * @param  mixed            $relation
     * @param  Model|Collection $models
     * @return void
     *
     * @throws InvalidArgumentException
     */
    public function detach($relation, Model | Collection $models)
    {
        if ($models instanceof Model) {
            $models = new Collection([$models]);
        }

        $this->dynamicRelations()
            ->where('relation', $relation)
            ->whereIn('result_id', $models->pluck('id')->all())
            ->delete();
    }

    /**
     * Get new instance of a dynamic relation model.
     *
     * @param  Model|null $model
     * @return Relation
     */
    public function newDynamicRelationModel(Model $model = null)
    {
        $bridge = $this->dynamicRelations()->make();

        if (! is_null($model)) {
            $bridge->result_id = $model->getKey();
            $bridge->result_type = get_class($model);
        }

        return $bridge;
    }

    /**
     * Get dynamic relation model namespace.
     *
     * @return string
     */
    public function getDynamicRelationModel()
    {
        return property_exists($this, 'relationsModel')
            ? $this->relationsModel
            : DynamicRelation::class;
    }

    /**
     * Determine if the given key is a relationship method on the model.
     *
     * @param  string $key
     * @return bool
     */
    public function isRelation($key)
    {
        return parent::isRelation($key) || $this->isDynamicRelation($key);
    }

    /**
     * Determine's whether the given relation name is a dynamic relation.
     *
     * @param  string $key
     * @return bool
     */
    public function isDynamicRelation($relation)
    {
        if ($relation == 'dynamicRelations') {
            return false;
        }

        return (bool) $this->getRelationValue('dynamicRelations')
            ->where('relation', $relation)
            ->first();
    }

    /**
     * Get a relationship value from a method.
     *
     * @param  string $method
     * @return mixed
     *
     * @throws \LogicException
     */
    protected function getRelationshipFromMethod($method)
    {
        if (! $this->isDynamicRelation($method)) {
            return parent::getRelationshipFromMethod($method);
        }

        $relation = $this->getDynamicRelationship($method);

        return tap($relation->getResults(), function ($results) use ($method) {
            $this->setRelation($method, $results);
        });
    }

    /**
     * Get dynamic relationship.
     *
     * @param  string                       $relation
     * @return HasOneThrough|HasManyThrough
     */
    public function getDynamicRelationship($relation): HasOneThrough|HasManyThrough
    {
        $relatedModel = $this->getDynamicRelatedModel($relation);

        if (! $this->isDynamicRelationMany($relation)) {
            $relationInstance = $this->hasOneThrough(
                related: $relatedModel,
                through: $this->getDynamicRelationModel(),
                firstKey: 'model_id',
                secondKey: 'id',
                localKey: 'id',
                secondLocalKey: 'result_id'
            );
        } else {
            $relationInstance = $this->hasManyThrough(
                related: $relatedModel,
                through: $this->getDynamicRelationModel(),
                firstKey: 'model_id',
                secondKey: 'id',
                localKey: 'id',
                secondLocalKey: 'result_id'
            );
        }

        return $relationInstance->where(function ($subQuery) use ($relation, $relatedModel) {
            $subQuery
                ->where('relation', $relation)
                ->where('model_type', static::class)
                ->where('result_type', $relatedModel);
        });
    }

    /**
     * Determine's whether the dynamic relation with the given name is a many
     * relation.
     *
     * @param  string $relation
     * @return bool
     */
    public function isDynamicRelationMany($relation)
    {
        if ($relation == 'dynamicRelations') {
            return false;
        }

        return $this->getRelationValue('dynamicRelations')
            ->where('relation', $relation)
            ->first()->is_many ?? false;
    }

    /**
     * Get dynamic related model namespace.
     *
     * @param  string $relation
     * @return string
     */
    public function getDynamicRelatedModel($relation)
    {
        return $this->getRelationValue('dynamicRelations')
            ->where('relation', $relation)
            ->first()->result_type;
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * @param  string $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        try {
            return parent::__call($method, $parameters);
        } catch (BadMethodCallException $e) {
            if ($this->isDynamicRelation($method)) {
                return $this->getDynamicRelationship($method);
            }

            throw $e;
        }
    }

    /**
     * Initialize HasDynamicRelations trait.
     *
     * @return void
     */
    protected function initializeHasDynamicRelations()
    {
        if (isset(static::$initialized[static::class])) {
            static::$initialized[static::class] = true;

            return;
        }

        $this->getDynamicRelationModel()::where('relation_type', static::class)
            ->select('relation')
            ->get()
            ->unique('relation')
            ->each(fn (DynamicRelation $bridge) => $this->setDynamicRelationResolver($bridge));
    }

    /**
     * Set dynamic relation resolver.
     *
     * @param  DynamicRelation $bridge
     * @return void
     */
    protected function setDynamicRelationResolver(DynamicRelation $bridge)
    {
        static::resolveRelationUsing(
            $bridge->relation,
            function () use ($bridge) {
                return $this->getDynamicRelationship($bridge->relation);
            }
        );
    }
}
