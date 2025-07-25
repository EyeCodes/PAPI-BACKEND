<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyProgramRule extends Model
{
    use HasFactory;

    protected $table = 'loyalty_program_rules';

    protected $fillable = [
        'loyalty_program_id',
        'rule_name',
        'rule_type',
        'points_earned',
        'amount_per_point',
        'min_purchase_amount',
        'product_category_id',
        'product_item_id',
        'is_active',
        'active_from_date',
        'active_to_date',
        'usage_limit',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'active_from_date' => 'date',
        'active_to_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Belongs to a loyalty program.
     */
    public function loyaltyProgram(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class);
    }

    public function rewards()
    {
        return $this->hasMany(LoyaltyReward::class);
    }
    
    /**
     * Belongs to a product category (optional).
     */
    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    /**
     * Belongs to a product item (optional).
     */
    public function productItem(): BelongsTo
    {
        return $this->belongsTo(ProductItem::class);
    }
}
