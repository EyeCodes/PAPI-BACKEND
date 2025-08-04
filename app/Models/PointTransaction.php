<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'program_id',
        'points',
        'transaction_type',
        'source',
        'notes'
    ];

    protected $casts = [
        'points' => 'integer'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function program()
    {
        return $this->belongsTo(LoyaltyProgram::class);
    }

    public function redemption()
    {
        return $this->hasOne(RewardRedemption::class, 'transaction_id');
    }

    public function scopeEarned($query)
    {
        return $query->where('transaction_type', 'earn');
    }

    public function scopeRedeemed($query)
    {
        return $query->where('transaction_type', 'redeem');
    }

    public function scopeExpired($query)
    {
        return $query->where('transaction_type', 'expire');
    }
}