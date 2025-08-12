<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessType extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'description',
    ];

    /**
     * Get the companies associated with the business type.
     */
    public function companies()
    {
        return $this->hasMany(Company::class);
    }

    /**
     * Get all business types.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getBusinessTypes()
    {
        return self::all();
    }
}
