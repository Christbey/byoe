<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopLocationResource extends JsonResource
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
            'shop_id' => $this->shop_id,
            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2,
            'city' => $this->city,
            'state' => $this->state,
            'zip_code' => $this->zip_code,
            'is_primary' => $this->is_primary,
            'geocoded_at' => $this->geocoded_at,
            'full_address' => $this->fullAddress(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // GPS coordinates only if user has access
            $this->mergeWhen($this->canViewCoordinates($request->user()), [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ]),

            // Relationships
            'shop' => ShopResource::make($this->whenLoaded('shop')),
        ];
    }

    /**
     * Determine if the authenticated user can view GPS coordinates.
     */
    private function canViewCoordinates($user): bool
    {
        if (! $user) {
            return false;
        }

        // Shop owners, providers, and admins can view coordinates
        return $user->hasRole(['shop_owner', 'provider', 'admin']);
    }
}
