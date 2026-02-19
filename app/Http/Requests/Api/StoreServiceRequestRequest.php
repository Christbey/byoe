<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shop_location_id' => ['required', 'exists:shop_locations,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'skills_required' => ['nullable', 'array'],
            'service_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'price' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
