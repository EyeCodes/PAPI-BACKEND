<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltyReward extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_id',
        'name',
        'description',
        'points_required',
        'is_active',
        'valid_from',
        'valid_to',
        'image_url',
        'stock_quantity'
    ];

    protected $casts = [
        'points_required' => 'integer',
        'is_active' => 'boolean',
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
        'stock_quantity' => 'integer'
    ];

    public function program()
    {
        return $this->belongsTo(LoyaltyProgram::class);
    }

    public function redemptions()
    {
        return $this->hasMany(RewardRedemption::class);
    }

    public function isAvailable()
    {
        return $this->is_active 
            && now()->between($this->valid_from, $this->valid_to)
            && ($this->stock_quantity === null || $this->stock_quantity > 0);
    }

    public function decreaseStock()
    {
        if ($this->stock_quantity !== null) {
            $this->decrement('stock_quantity');
        }
    }
}