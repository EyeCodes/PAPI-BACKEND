<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\PointsRuleType;

class PointsRule extends Model
{
    protected $fillable = [
        'type',
        'parameters',
        'conditions',
        'priority',
        'associated_entity_type',
        'associated_entity_id',
    ];

    protected $casts = [
        'type' => PointsRuleType::class,
        'parameters' => 'array',
        'conditions' => 'array',
        'priority' => 'integer',
    ];

    public function associatedEntity()
    {
        return $this->morphTo(__FUNCTION__, 'associated_entity_type', 'associated_entity_id');
    }
}
