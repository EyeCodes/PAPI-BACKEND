<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Rewards extends Model
{
    use HasFactory;

        protected $table = 'rewards';

    protected $fillable = [
        'reward_name',
        'reward_type',
        'point_cost',
        'discount_value',
        'discount_percentage',
        'item_id',
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
        'max_redemption_rate' => 'integer',
        'expiration_days' => 'integer',
    ];

}
