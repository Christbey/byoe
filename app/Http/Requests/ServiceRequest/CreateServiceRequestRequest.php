<?php

namespace App\Http\Requests\ServiceRequest;

use Illuminate\Foundation\Http\FormRequest;

class CreateServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(['shop_owner', 'shop_manager', 'admin']);
    }

    public function rules(): array
    {
        return [
            'shop_location_id' => ['required', 'exists:shop_locations,id'],
            'service_date' => ['required', 'date', 'after:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'required_skills' => ['nullable', 'array'],
            'required_skills.*' => ['string', 'max:255'],
            'service_price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'shop_location_id.required' => 'Please select a shop location.',
            'shop_location_id.exists' => 'The selected shop location is invalid.',
            'service_date.required' => 'Please select a service date.',
            'service_date.date' => 'Please provide a valid service date.',
            'service_date.after' => 'Service date must be in the future.',
            'start_time.required' => 'Please specify a start time.',
            'start_time.date_format' => 'Start time must be in the format HH:MM.',
            'end_time.required' => 'Please specify an end time.',
            'end_time.date_format' => 'End time must be in the format HH:MM.',
            'end_time.after' => 'End time must be after start time.',
            'required_skills.array' => 'Required skills must be provided as a list.',
            'required_skills.*.string' => 'Each skill must be a valid text entry.',
            'required_skills.*.max' => 'Each skill cannot exceed 255 characters.',
            'service_price.required' => 'Please specify a service price.',
            'service_price.numeric' => 'Service price must be a valid number.',
            'service_price.min' => 'Service price cannot be negative.',
            'description.max' => 'Service description cannot exceed 1000 characters.',
        ];
    }
}
