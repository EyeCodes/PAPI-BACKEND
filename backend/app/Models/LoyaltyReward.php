<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\LoyaltyProgramRule;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LoyaltyReward extends Model
{
    use HasFactory;

    protected $table = 'loyalty_rewards';

    protected $fillable = [
        'loyalty_program_rule_id',
        'reward_name',
        'description',
        'reward_type',
        'point_cost',
        'discount_value',
        'discount_percentage',
        // 'item_id',
        'voucher_code',
        'is_active',
        'max_redemption_rate',
        'expiration_days',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'point_cost' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Reward belongs to a loyalty program.
     */
    public function loyaltyProgramRule(): BelongsTo
    {
        return $this->belongsTo(loyaltyProgramRule::class);
    }

    /**
     * Reward may be linked to a product item.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(ProductItem::class);
    }

    public function users():BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withTimestamps();
}
}
