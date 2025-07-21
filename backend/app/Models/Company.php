<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Cache\Events\RetrievingKey;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $company = 'companies';
    protected $fillable = [
        'company_name',
        'display_name',
        'company_logo',
        'business_type',
        'user_id',
        'telephone_contact_1',
        'telephone_contact_2',
        'email_contact_1',
        'email_contact_2',
        'barangay',
        'city_municipality',
        'province',
        'region',
        'zipcode',
        'country',
        'street',
        'business_registration_number',
        'tin_number',
        'currency_code',
    ];

    protected $casts = [
        'business_registration_number' => 'encrypted',
        'tin_number' => 'encrypted',
    ];

    public function user(): BelongsToMany
    {
        return $this->BelongsToMany(User::class);
    }
    public static function rules(): array
    {
        return [
            'business_registration_number' => [
                'required',
                'string',
                'max:50',
                'unique:companies,business_registration_number',
                'regex:/^[A-Z0-9\-]+$/i'
            ],
            'tin_number' => [
                'required',
                'string',
                'max:15',
                'unique:companies,tin_number',
                'regex:/^\d{9,12}$/'
            ],
            // ...other rules
        ];
    }
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
}
