<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class LocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(['shop_owner', 'shop_manager', 'admin']);
    }

    public function rules(): array
    {
        return [
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'size:2'],
            'zip_code' => ['required', 'string', 'max:10'],
            'is_primary' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'address_line_1.required' => 'Please enter a street address.',
            'city.required' => 'Please enter a city.',
            'state.required' => 'Please enter a 2-letter state code.',
            'state.size' => 'State must be a 2-letter code (e.g. CA).',
            'zip_code.required' => 'Please enter a ZIP code.',
        ];
    }
}
