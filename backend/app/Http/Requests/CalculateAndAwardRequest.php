<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CalculateAndAwardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'purchase_amount' => 'required|numeric|min:0',
            'product_category_id' => 'nullable|exists:product_categories,id',
            'product_item_id' => 'nullable|exists:product_items,id',
            'loyalty_program_id' => 'required|exists:loyalty_programs,id',
        ];
    }
}
