<?php

namespace App\Http\Resources\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceRequestResource extends JsonResource
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
            'shop_location_id' => $this->shop_location_id,
            'title' => $this->title,
            'description' => $this->description,
            'skills_required' => $this->skills_required,
            'service_date' => $this->service_date,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'price' => (float) $this->price,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'status_variant' => $this->status_variant,
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships
            'shop_location' => ShopLocationResource::make($this->whenLoaded('shopLocation')),
            'booking' => BookingResource::make($this->whenLoaded('booking')),

            // Sensitive payment data only for shop owner or admins
            $this->mergeWhen($this->canViewPaymentDetails($request->user()), [
                'stripe_payment_intent_id' => $this->stripe_payment_intent_id,
                'stripe_client_secret' => $this->stripe_client_secret,
            ]),
        ];
    }

    /**
     * Determine if the authenticated user can view payment details.
     */
    private function canViewPaymentDetails(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        // Load shop location if not already loaded
        $this->loadMissing('shopLocation.shop');

        return $user->id === $this->shopLocation->shop->user_id
            || $user->hasRole('admin');
    }
}
