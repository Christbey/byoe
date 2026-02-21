<?php

namespace App\Actions;

use App\Models\Booking;
use App\Models\Provider;
use App\Models\ServiceRequest;
use App\Services\BookingService;

class AcceptServiceRequestAction
{
    /**
     * Create a new action instance.
     */
    public function __construct(
        protected BookingService $bookingService
    ) {}

    /**
     * Execute the action.
     */
    public function __invoke(ServiceRequest $serviceRequest, Provider $provider): Booking
    {
        return $this->bookingService->acceptServiceRequest($serviceRequest, $provider);
    }
}
