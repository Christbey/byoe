<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RatingResource extends JsonResource
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
            'rater_id' => $this->rater_id,
            'ratee_id' => $this->ratee_id,
            'rater_type' => $this->rater_type,
            'rating' => $this->rating,
            'stars_formatted' => $this->starsFormatted(),
            'comment' => $this->comment,
            'is_positive' => $this->isPositive(),
            'is_negative' => $this->isNegative(),
            'is_neutral' => $this->isNeutral(),
            'is_from_shop' => $this->isFromShop(),
            'is_from_provider' => $this->isFromProvider(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships
            'booking' => BookingResource::make($this->whenLoaded('booking')),
            'rater' => UserResource::make($this->whenLoaded('rater')),
            'ratee' => UserResource::make($this->whenLoaded('ratee')),
        ];
    }
}
