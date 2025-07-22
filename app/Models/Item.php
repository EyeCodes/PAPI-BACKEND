<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
        'sku',
        'barcode',
        'quantity',
        'image_url',
        'expiration_date'
    ];

    protected $casts = [
        'expiration_date' => 'date'
    ];
}