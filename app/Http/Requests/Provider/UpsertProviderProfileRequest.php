<?php

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

class UpsertProviderProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['provider', 'admin']);
    }

    public function rules(): array
    {
        return [
            'bio' => ['nullable', 'string', 'max:1000'],
            'skills' => ['nullable', 'array'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:50'],
            'service_area_max_miles' => ['nullable', 'integer', 'min:1', 'max:500'],
            'preferred_zip_codes' => ['nullable', 'array'],
            'preferred_zip_codes.*' => ['string', 'regex:/^\d{5}(-\d{4})?$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'bio.max' => 'Bio cannot exceed 1000 characters.',
            'years_experience.min' => 'Years of experience cannot be negative.',
            'years_experience.max' => 'Please enter a valid number of years of experience.',
            'service_area_max_miles.min' => 'Service area must be at least 1 mile.',
            'service_area_max_miles.max' => 'Service area cannot exceed 500 miles.',
            'preferred_zip_codes.*.regex' => 'Each ZIP code must be in 5-digit or ZIP+4 format (e.g. 90210 or 90210-1234).',
        ];
    }
}
