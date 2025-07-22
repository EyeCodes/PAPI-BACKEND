<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\PasswordComplexityRule;

class StoreUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'string', 'min:8', 'confirmed', new PasswordComplexityRule()],
            'gender' => 'required|string',
            'home_address' => 'required|string',
            // 'current_address' => 'required|string',
            'date_of_birth' => ['required', 'date', 'before:today'],
            'contact_num' => ['required', 'digits_between:7,15'],
            // 'avatar' => ['nullable', 'image', 'max:2048'], 
            // 'password' => [
            //     'required',
            //     'string',
            //     'min:8',
            //     'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/'
            // ],
        ];
    }

    public function messages(): array
    {
        return [
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
