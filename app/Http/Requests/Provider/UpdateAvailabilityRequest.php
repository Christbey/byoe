<?php

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['provider', 'admin']);
    }

    public function rules(): array
    {
        return [
            'schedule' => ['required', 'array'],
            'schedule.*.available' => ['required', 'boolean'],
            'schedule.*.start' => ['required_if:schedule.*.available,true', 'nullable', 'date_format:H:i'],
            'schedule.*.end' => ['required_if:schedule.*.available,true', 'nullable', 'date_format:H:i'],
            'blackout_dates' => ['nullable', 'array'],
            'blackout_dates.*' => ['date_format:Y-m-d'],
            'min_notice_hours' => ['required', 'integer', 'min:0', 'max:168'],
        ];
    }

    public function messages(): array
    {
        return [
            'schedule.required' => 'A weekly schedule is required.',
            'schedule.*.available.required' => 'Each day must specify availability.',
            'schedule.*.start.required_if' => 'A start time is required for available days.',
            'schedule.*.end.required_if' => 'An end time is required for available days.',
            'schedule.*.start.date_format' => 'Start time must be in HH:MM format.',
            'schedule.*.end.date_format' => 'End time must be in HH:MM format.',
            'blackout_dates.*.date_format' => 'Blackout dates must be in YYYY-MM-DD format.',
            'min_notice_hours.max' => 'Minimum notice cannot exceed 168 hours (1 week).',
        ];
    }
}
