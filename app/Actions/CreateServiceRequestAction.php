<?php

namespace App\Actions;

use App\Models\ServiceRequest;
use App\Models\ShopLocation;

class CreateServiceRequestAction
{
    /**
     * Execute the action.
     */
    public function __invoke(ShopLocation $shopLocation, array $data): ServiceRequest
    {
        return ServiceRequest::create([
            'shop_location_id' => $shopLocation->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'skills_required' => $data['skills_required'] ?? [],
            'service_date' => $data['service_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'price' => $data['price'],
            'status' => 'pending_payment',
            'expires_at' => $data['expires_at'] ?? now()->addHours(
                config('marketplace.service_request_expiration_hours', 72)
            ),
        ]);
    }
}
