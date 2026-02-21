<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderResource extends JsonResource
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
            'user_id' => $this->user_id,
            'industry_id' => $this->industry_id,
            'bio' => $this->bio,
            'skills' => $this->skills,
            'years_experience' => $this->years_experience,
            'average_rating' => $this->average_rating,
            'total_ratings' => $this->total_ratings,
            'completed_bookings' => $this->completed_bookings,
            'is_active' => $this->is_active,
            'availability_schedule' => $this->availability_schedule,
            'blackout_dates' => $this->blackout_dates,
            'min_notice_hours' => $this->min_notice_hours,
            'certifications' => $this->certifications,
            'service_area_max_miles' => $this->service_area_max_miles,
            'preferred_zip_codes' => $this->preferred_zip_codes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Computed attributes
            'available_skills' => $this->availableSkills(),
            'available_templates' => $this->availableTemplates(),
            'profile_completeness' => $this->profileCompleteness(),
            'can_receive_payouts' => $this->canReceivePayouts(),

            // Relationships
            'user' => UserResource::make($this->whenLoaded('user')),
            'industry' => IndustryResource::make($this->whenLoaded('industry')),

            // Upcoming and completed bookings counts
            'upcoming_bookings_count' => $this->whenCounted('bookings'),
            'completed_bookings_count' => $this->completed_bookings,

            // Stripe account (only for the provider themselves or admins)
            $this->mergeWhen($this->canViewStripeAccount($request->user()), [
                'stripe_account' => ProviderStripeAccountResource::make($this->whenLoaded('stripeAccount')),
            ]),
        ];
    }

    /**
     * Determine if the authenticated user can view Stripe account details.
     */
    private function canViewStripeAccount($user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->id === $this->user_id || $user->hasRole('admin');
    }
}
