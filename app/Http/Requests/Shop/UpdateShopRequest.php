<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(['shop_owner', 'shop_manager', 'admin']);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:20'],
            'website' => ['nullable', 'url', 'max:255'],
            'operating_hours' => ['nullable', 'array'],
            'industry_id' => ['nullable', 'exists:industries,id'],
            'custom_skills' => ['nullable', 'array'],
            'ein' => ['nullable', 'string', 'regex:/^\d{2}-\d{7}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter a name for your shop.',
            'name.max' => 'Shop name cannot exceed 255 characters.',
            'description.max' => 'Shop description cannot exceed 1000 characters.',
            'website.url' => 'Please enter a valid website URL.',
            'industry_id.exists' => 'The selected industry is invalid.',
            'ein.regex' => 'EIN must be in the format 12-3456789.',
        ];
    }
}
