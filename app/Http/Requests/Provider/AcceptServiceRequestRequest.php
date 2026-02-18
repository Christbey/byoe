<?php

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

class AcceptServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('provider') && $this->user()->provider !== null;
    }

    public function rules(): array
    {
        return [
            'service_request_id' => ['required', 'exists:service_requests,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'service_request_id.required' => 'Please specify the service request to accept.',
            'service_request_id.exists' => 'The selected service request does not exist.',
        ];
    }
}
