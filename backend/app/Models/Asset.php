<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asset extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'value',
        'type',
        'currency',
        'acquisition_date',
        'metadata',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'acquisition_date' => 'date',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the asset.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get asset types
     */
    public static function getTypes(): array
    {
        return [
            'cash' => 'Cash',
            'bank_account' => 'Bank Account',
            'investment' => 'Investment',
            'property' => 'Property',
            'vehicle' => 'Vehicle',
            'jewelry' => 'Jewelry',
            'electronics' => 'Electronics',
            'furniture' => 'Furniture',
            'other' => 'Other',
        ];
    }

    /**
     * Scope to get assets by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get total value of assets for a user
     */
    public static function getTotalValue(int $userId): float
    {
        return static::where('user_id', $userId)->sum('value');
    }
}
