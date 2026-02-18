<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AvailabilityController extends Controller
{
    public function show(Request $request): Response
    {
        $provider = $request->user()->provider;

        $defaultSchedule = collect(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])
            ->mapWithKeys(fn ($day) => [$day => ['available' => false, 'start' => '08:00', 'end' => '17:00']])
            ->toArray();

        $schedule = $provider?->availability_schedule ?? $defaultSchedule;
        $blackoutDates = $provider?->blackout_dates ?? [];
        $minNoticeHours = $provider?->min_notice_hours ?? 24;

        return Inertia::render('provider/Availability', [
            'needsProfile' => ! $provider,
            'schedule' => $schedule,
            'blackoutDates' => $blackoutDates,
            'minNoticeHours' => $minNoticeHours,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $provider = $request->user()->provider;

        if (! $provider) {
            return redirect()->back()->with('error', 'Provider profile not found.');
        }

        $validated = $request->validate([
            'schedule' => ['required', 'array'],
            'schedule.*.available' => ['required', 'boolean'],
            'schedule.*.start' => ['required_if:schedule.*.available,true', 'nullable', 'date_format:H:i'],
            'schedule.*.end' => ['required_if:schedule.*.available,true', 'nullable', 'date_format:H:i'],
            'blackout_dates' => ['nullable', 'array'],
            'blackout_dates.*' => ['date_format:Y-m-d'],
            'min_notice_hours' => ['required', 'integer', 'min:0', 'max:168'],
        ]);

        $provider->update([
            'availability_schedule' => $validated['schedule'],
            'blackout_dates' => $validated['blackout_dates'] ?? [],
            'min_notice_hours' => $validated['min_notice_hours'],
        ]);

        return redirect()->back()->with('success', 'Availability schedule updated.');
    }
}
