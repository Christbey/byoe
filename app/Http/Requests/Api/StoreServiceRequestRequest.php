<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization happens here BEFORE validation
        return $this->user()->can('create', \App\Models\ServiceRequest::class);
    }

    public function rules(): array
    {
        return [
            'shop_location_id' => ['required', 'exists:shop_locations,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'skills_required' => ['nullable', 'array'],
            'service_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'price' => ['nullable', 'numeric', 'gt:0'],
        ];
    }
}
