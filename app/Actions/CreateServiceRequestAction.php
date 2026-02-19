<?php

namespace App\Actions;

use App\Models\ServiceRequest;
use App\Models\ShopLocation;
use Carbon\Carbon;

class CreateServiceRequestAction
{
    /**
     * Execute the action.
     *
     * Computes the shift duration, handles overnight shifts, calculates price
     * from the platform hourly rate, and sets the expiration timestamp before
     * persisting the service request.
     */
    public function __invoke(ShopLocation $shopLocation, array $data): ServiceRequest
    {
        $startCarbon = Carbon::parse($data['service_date'].' '.$data['start_time']);
        $endCarbon = Carbon::parse($data['service_date'].' '.$data['end_time']);

        // Handle overnight shifts: if end <= start the shift crosses midnight.
        if ($endCarbon <= $startCarbon) {
            $endCarbon->addDay();
        }

        $hours = $startCarbon->diffInMinutes($endCarbon) / 60;
        $price = round(config('marketplace.hourly_rate') * $hours, 2);

        return ServiceRequest::create([
            'shop_location_id' => $shopLocation->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'skills_required' => $data['skills_required'] ?? [],
            'service_date' => $data['service_date'],
            'start_time' => $startCarbon->toDateTimeString(),
            'end_time' => $endCarbon->toDateTimeString(),
            'price' => $price,
            'status' => 'pending_payment',
            'expires_at' => now()->addHours(config('marketplace.service_request_expiration_hours', 72)),
        ]);
    }
}
