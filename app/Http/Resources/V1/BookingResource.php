<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
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
            'service_request_id' => $this->service_request_id,
            'provider_id' => $this->provider_id,
            'service_price' => (float) $this->service_price,
            'platform_fee' => (float) $this->platform_fee,
            'provider_payout' => (float) $this->provider_payout,
            'total_amount' => (float) $this->totalAmount(),
            'status' => $this->status,
            'status_label' => $this->status_label,
            'status_variant' => $this->status_variant,
            'accepted_at' => $this->accepted_at,
            'completed_at' => $this->completed_at,
            'cancelled_at' => $this->cancelled_at,
            'cancellation_reason' => $this->cancellation_reason,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships
            'service_request' => ServiceRequestResource::make($this->whenLoaded('serviceRequest')),
            'provider' => ProviderResource::make($this->whenLoaded('provider')),
            'payment' => PaymentResource::make($this->whenLoaded('payment')),
            'payout' => PayoutResource::make($this->whenLoaded('payout')),
            'ratings' => RatingResource::collection($this->whenLoaded('ratings')),
        ];
    }
}
