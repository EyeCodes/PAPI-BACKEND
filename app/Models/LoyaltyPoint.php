<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltyPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'program_id',
        'points_balance'
    ];

    protected $casts = [
        'points_balance' => 'integer'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function program()
    {
        return $this->belongsTo(LoyaltyProgram::class);
    }

    public function transactions()
    {
        return $this->hasMany(PointTransaction::class, 'user_id', 'user_id')
            ->where('program_id', $this->program_id);
    }

    // Business Logic Methods
    public function addPoints(int $points, string $source, string $notes = null)
    {
        $this->increment('points_balance', $points);
        
        PointTransaction::create([
            'user_id' => $this->user_id,
            'program_id' => $this->program_id,
            'points' => $points,
            'transaction_type' => 'earn',
            'source' => $source,
            'notes' => $notes
        ]);
    }

    public function deductPoints(int $points, string $source, string $notes = null)
    {
        $this->decrement('points_balance', $points);
        
        PointTransaction::create([
            'user_id' => $this->user_id,
            'program_id' => $this->program_id,
            'points' => $points,
            'transaction_type' => 'redeem',
            'source' => $source,
            'notes' => $notes
        ]);
    }
}