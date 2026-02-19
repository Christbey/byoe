<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(['shop_owner', 'shop_manager', 'admin']);
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
            'end_time' => ['required', 'date_format:H:i'],
        ];
    }

    public function messages(): array
    {
        return [
            'shop_location_id.required' => 'Please select a location for this service request.',
            'shop_location_id.exists' => 'The selected location is invalid.',
            'title.required' => 'Please enter a title for the service request.',
            'description.required' => 'Please enter a description.',
            'service_date.required' => 'Please select a service date.',
            'service_date.after_or_equal' => 'The service date must be today or in the future.',
            'start_time.required' => 'Please enter a start time.',
            'start_time.date_format' => 'Start time must be in HH:MM format.',
            'end_time.required' => 'Please enter an end time.',
            'end_time.date_format' => 'End time must be in HH:MM format.',
        ];
    }
}
