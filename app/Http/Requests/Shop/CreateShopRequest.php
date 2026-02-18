<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class CreateShopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(['shop_owner', 'admin']);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter a name for your shop.',
            'name.max' => 'Shop name cannot exceed 255 characters.',
            'description.max' => 'Shop description cannot exceed 1000 characters.',
            'is_active.boolean' => 'Shop status must be either active or inactive.',
        ];
    }
}
