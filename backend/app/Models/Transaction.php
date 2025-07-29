<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\PointsService;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'merchant_id',
        'amount',
        'awarded_points',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function calculateAmount(): float
    {
        return $this->items->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });
    }

    public function getAmountAttribute($value)
    {
        // If amount is null, calculate it from items
        if ($value === null) {
            return $this->calculateAmount();
        }
        return $value;
    }

    protected static function booted()
    {
        static::created(function ($transaction) {
            // Only calculate and award points if the transaction has a user
            if ($transaction->user) {
                $pointsService = new PointsService();
                $pointsService->awardPoints($transaction);
            }
        });
    }
}
