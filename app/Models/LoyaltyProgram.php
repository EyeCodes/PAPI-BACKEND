<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoyaltyProgram extends Model
{
    use HasFactory;

    protected $table = 'loyaltyPrograms';

    protected $fillable = [
        'company_id',
        'program_name',
        'description',
        'program_type',
        'is_active',
        'start_date',
        'end_date',
        'instructions'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date'
    ];

    public function company(){
        return $this->belongsTo(Companies::class);
    }

    public function rewards()
    {
        return $this->hasMany(LoyaltyReward::class);
    }

    public function members()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('points_balance')
            ->using(LoyaltyPoint::class);
    }

}
