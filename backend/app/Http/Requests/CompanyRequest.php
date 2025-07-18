<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\ValidCompanyEmail;

class CompanyRequest extends FormRequest
{
    // public function authorize(): bool
    // {
    //     return true;
    // }

    public function rules(): array
    {
        return [
            'company_name' => 'required|string|max:255',
            'display_name' => 'required|string|max:255',
            'company_logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'business_type' => 'required|string|max:255',

            'telephone_contact_1' => 'required|string|max:255',
            'telephone_contact_2' => 'required|string|max:255',

            'email_contact_1' => ['required', 'email', new ValidCompanyEmail],
            'email_contact_2' => ['required', 'email', new ValidCompanyEmail],

            'barangay' => 'required|string|max:255',
            'city_municipality' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'region' => 'required|string|max:255',
            'zipcode' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'currency_code' => 'required|string|max:255',

            'registration_number' => 'required|string|max:255',
            'tin_number' => 'required|string|max:255',
        ];
    }
}
