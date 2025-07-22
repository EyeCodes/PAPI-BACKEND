<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Companies extends Model
{
    protected $company = 'companies';
    protected $fillable = [
        'company_name',
        'display_name',
        'company_logo',
        'business_type',
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
        'registration_number',
        'tin_number',
        'currency_code',
    ];

}
