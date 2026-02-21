<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
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
            'amount' => $this->amount,
            'formatted_amount' => $this->formattedAmount(),
            'currency' => $this->currency,
            'status' => $this->status,
            'payment_method_type' => $this->payment_method_type,
            'last_four' => $this->last_four,
            'paid_at' => $this->paid_at,
            'failure_message' => $this->failure_message,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships
            'booking' => BookingResource::make($this->whenLoaded('booking')),

            // Sensitive Stripe data only for payment creator or admins
            $this->mergeWhen($this->canViewStripeDetails($request->user()), [
                'stripe_payment_intent_id' => $this->stripe_payment_intent_id,
                'stripe_client_secret' => $this->stripe_client_secret,
            ]),
        ];
    }

    /**
     * Determine if the authenticated user can view Stripe details.
     */
    private function canViewStripeDetails($user): bool
    {
        if (! $user) {
            return false;
        }

        // Load booking if not already loaded
        $this->loadMissing('booking.serviceRequest.shopLocation.shop');

        return $user->id === $this->booking->serviceRequest->shopLocation->shop->user_id
            || $user->hasRole('admin');
    }
}
