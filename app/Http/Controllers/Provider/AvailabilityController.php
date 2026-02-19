<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\UpdateAvailabilityRequest;
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

        return Inertia::render('provider/Availability', [
            'schedule' => $provider?->availability_schedule ?? $defaultSchedule,
            'blackoutDates' => $provider?->blackout_dates ?? [],
            'minNoticeHours' => $provider?->min_notice_hours ?? 24,
        ]);
    }

    public function update(UpdateAvailabilityRequest $request): RedirectResponse
    {
        $provider = $request->user()->provider;

        $validated = $request->validated();

        $provider->update([
            'availability_schedule' => $validated['schedule'],
            'blackout_dates' => $validated['blackout_dates'] ?? [],
            'min_notice_hours' => $validated['min_notice_hours'],
        ]);

        return redirect()->back()->with('success', 'Availability schedule updated.');
    }
}
