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
            'vetting_status' => $this->vetting_status,
            'background_check_status' => $this->background_check_status,
            'identity_verified_at' => $this->identity_verified_at,
            'vetting_completed_at' => $this->vetting_completed_at,
            'last_reviewed_at' => $this->last_reviewed_at,
            'trust_score' => $this->trust_score,
            'reliability_score' => $this->reliability_score,
            'cancellation_count' => $this->cancellation_count,
            'cancellation_rate' => $this->cancellation_rate,
            'no_show_count' => $this->no_show_count,
            'dispute_count' => $this->dispute_count,
            'completed_without_issue_count' => $this->completed_without_issue_count,
            'trust_notes' => $this->trust_notes,
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
            'trust_tier' => $this->trustTier(),
            'trust_action_items' => $this->trustActionItems(),

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
