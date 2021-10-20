<?php

namespace AwStudio\DynamicRelations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DynamicRelation extends Model
{
    /**
     * Attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'key', 'is_many', 'model_type', 'model_id', 'result_type', 'result_id',
    ];

    /**
     * Database table name.
     *
     * @var string
     */
    public $table = 'dynamic_relations';

    /**
     * Attribute casts.
     *
     * @var array
     */
    protected $casts = [
        'is_many' => 'boolean',
    ];

    /**
     * Result model.
     *
     * @return MorphTo
     */
    public function result(): MorphTo
    {
        return $this->morphTo('result');
    }
}
