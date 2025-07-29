<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMerchantPoints extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'merchant_id',
        'points',
        'total_earned',
        'total_spent',
        'last_earned_at',
        'last_spent_at'
    ];

    protected $casts = [
        'points' => 'integer',
        'total_earned' => 'integer',
        'total_spent' => 'integer',
        'last_earned_at' => 'datetime',
        'last_spent_at' => 'datetime',
    ];

    /**
     * Get the user that owns these points
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the merchant these points belong to
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * Add points to this merchant
     */
    public function addPoints(int $points): void
    {
        $this->points = ($this->attributes['points'] ?? 0) + $points;
        $this->total_earned = ($this->attributes['total_earned'] ?? 0) + $points;
        $this->last_earned_at = now();
        $this->save();
    }

    /**
     * Spend points from this merchant
     */
    public function spendPoints(int $points): bool
    {
        if (($this->attributes['points'] ?? 0) >= $points) {
            $this->points = ($this->attributes['points'] ?? 0) - $points;
            $this->total_spent = ($this->attributes['total_spent'] ?? 0) + $points;
            $this->last_spent_at = now();
            $this->save();
            return true;
        }
        return false;
    }

    /**
     * Check if user has enough points
     */
    public function hasEnoughPoints(int $points): bool
    {
        return ($this->attributes['points'] ?? 0) >= $points;
    }

    /**
     * Get points balance for display
     */
    public function getBalanceAttribute(): string
    {
        return number_format($this->attributes['points'] ?? 0) . ' points';
    }

    /**
     * Get total earned for display
     */
    public function getTotalEarnedAttribute(): string
    {
        $totalEarned = $this->attributes['total_earned'] ?? $this->points;
        return number_format($totalEarned) . ' points';
    }

    /**
     * Get total spent for display
     */
    public function getTotalSpentAttribute(): string
    {
        $totalSpent = $this->attributes['total_spent'] ?? 0;
        return number_format($totalSpent) . ' points';
    }
}
