<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayoutResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_id' => $this->booking_id,
            'provider_id' => $this->provider_id,
            'stripe_transfer_id' => $this->stripe_transfer_id,
            'amount' => $this->amount,
            'formatted_amount' => $this->formattedAmount(),
            'currency' => $this->currency,
            'status' => $this->status,
            'scheduled_for' => $this->scheduled_for,
            'paid_at' => $this->paid_at,
            'failure_message' => $this->failure_message,
            'is_due' => $this->isDue(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships
            'booking' => BookingResource::make($this->whenLoaded('booking')),
            'provider' => ProviderResource::make($this->whenLoaded('provider')),
        ];
    }
}
