<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'terms_accepted_at' => $this->terms_accepted_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Roles
            'roles' => $this->getRoleNames(),
            'is_shop_owner' => $this->isShopOwner(),
            'is_provider' => $this->isProvider(),
            'is_admin' => $this->isAdmin(),

            // Relationships
            'shop' => ShopResource::make($this->whenLoaded('shop')),
            'provider' => ProviderResource::make($this->whenLoaded('provider')),

            // Two-factor authentication status (don't expose secrets)
            'two_factor_enabled' => ! is_null($this->two_factor_secret),
            'two_factor_confirmed_at' => $this->two_factor_confirmed_at,
        ];
    }
}
