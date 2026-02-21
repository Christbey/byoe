<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class EmailPreferencesUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'daily_digest' => ['boolean'],
            'weekly_summary' => ['boolean'],
            'booking_reminders' => ['boolean'],
            'new_requests' => ['boolean'],
            'payment_notifications' => ['boolean'],
            'rating_notifications' => ['boolean'],
            'marketing' => ['boolean'],
        ];
    }
}
