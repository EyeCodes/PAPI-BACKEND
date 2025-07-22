<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltyTransaction extends Model
{
    use HasFactory;

    protected $table = 'loyalty_transaction'; 
    protected $fillable = [
        'user_id',
        'program_id',
        'item_id',
        'points',
        'transaction_type',
        'source',
        'notes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function program()
    {
        return $this->belongsTo(LoyaltyProgram::class, 'program_id');
    }

    public function item()
    {
        return $this->belongsTo(ProductItem::class, 'item_id');
    }
}
