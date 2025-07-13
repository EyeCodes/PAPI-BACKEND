<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LoyaltyProgram extends Model
{
    use HasFactory;

    protected $table = 'loyalty_programs';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'program_name',
        'description',
        'program_type',
        'is_active',
        'start_date',
        'end_date',
        'instructions',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Loyalty program belongs to a company.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
