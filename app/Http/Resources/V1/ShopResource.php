<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'phone' => $this->phone,
            'website' => $this->website,
            'operating_hours' => $this->operating_hours,
            'status' => $this->status,
            'industry_id' => $this->industry_id,
            'custom_skills' => $this->custom_skills,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Computed attributes
            'available_skills' => $this->availableSkills(),
            'available_templates' => $this->availableTemplates(),

            // Relationships
            'user' => UserResource::make($this->whenLoaded('user')),
            'industry' => IndustryResource::make($this->whenLoaded('industry')),
            'locations' => ShopLocationResource::collection($this->whenLoaded('locations')),
            'primary_location' => ShopLocationResource::make($this->whenLoaded('primaryLocation')),

            // Sensitive data only for shop owner or admins
            $this->mergeWhen($this->canViewSensitiveData($request->user()), [
                'ein' => $this->ein,
                'stripe_customer_id' => $this->stripe_customer_id,
                'stripe_payment_method_id' => $this->stripe_payment_method_id,
            ]),
        ];
    }

    /**
     * Determine if the authenticated user can view sensitive shop data.
     */
    private function canViewSensitiveData($user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->id === $this->user_id || $user->hasRole('admin');
    }
}
