<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RewardRedemption extends Model
{
    use HasFactory;

    protected $table = 'loyalty_redemptions';

    protected $fillable = [
        'user_id',
        'reward_id',
        'points_used',
        'status',
        'transaction_id'
    ];

    protected $casts = [
        'points_used' => 'integer'
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_FULFILLED = 'fulfilled';
    const STATUS_CANCELLED = 'cancelled';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reward()
    {
        return $this->belongsTo(LoyaltyReward::class);
    }

    public function transaction()
    {
        return $this->belongsTo(PointTransaction::class);
    }

    public function fulfill()
    {
        $this->update(['status' => self::STATUS_FULFILLED]);
        $this->reward->decreaseStock();
    }

    public function cancel()
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
        
        if ($this->transaction) {
            $this->user->loyaltyPoints()
                ->where('program_id', $this->reward->program_id)
                ->first()
                ->addPoints(
                    $this->points_used,
                    'redemption_cancellation',
                    "Refund for cancelled redemption #{$this->id}"
                );
        }
    }
}