<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rating extends Model
{
    /** @use HasFactory<\Database\Factories\RatingFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'booking_id',
        'rater_id',
        'ratee_id',
        'rater_type',
        'rating',
        'comment',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    /**
     * Get the booking for this rating.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the user who gave the rating.
     */
    public function rater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rater_id');
    }

    /**
     * Get the user who received the rating.
     */
    public function ratee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ratee_id');
    }

    /**
     * Check if the rater is a shop owner.
     */
    public function isFromShop(): bool
    {
        return $this->rater_type === 'shop';
    }

    /**
     * Check if the rater is a provider.
     */
    public function isFromProvider(): bool
    {
        return $this->rater_type === 'provider';
    }

    /**
     * Check if the rating is positive (4 or 5 stars).
     */
    public function isPositive(): bool
    {
        return $this->rating >= 4;
    }

    /**
     * Check if the rating is negative (1 or 2 stars).
     */
    public function isNegative(): bool
    {
        return $this->rating <= 2;
    }

    /**
     * Check if the rating is neutral (3 stars).
     */
    public function isNeutral(): bool
    {
        return $this->rating === 3;
    }

    /**
     * Get the rating as stars (e.g., "5/5").
     */
    public function starsFormatted(): string
    {
        return $this->rating.'/5';
    }
}
