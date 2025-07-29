<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Product extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'image',
        'price',
        'currency',
        'stock',
        'external_id',
        'source',
        'last_synced_at',
        'merchant_id',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'last_synced_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the merchant that owns the product.
     */
    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function pointsRules()
    {
        return $this->morphMany(PointsRule::class, 'associated_entity');
    }
}
