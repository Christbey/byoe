<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Provider extends Model
{
    /** @use HasFactory<\Database\Factories\ProviderFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'bio',
        'skills',
        'years_experience',
        'average_rating',
        'total_ratings',
        'completed_bookings',
        'is_active',
        'availability_schedule',
        'blackout_dates',
        'min_notice_hours',
        'certifications',
        'service_area_max_miles',
        'preferred_zip_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'availability_schedule' => 'array',
            'blackout_dates' => 'array',
            'years_experience' => 'integer',
            'min_notice_hours' => 'integer',
            'average_rating' => 'float',
            'total_ratings' => 'integer',
            'completed_bookings' => 'integer',
            'is_active' => 'boolean',
            'certifications' => 'array',
            'service_area_max_miles' => 'integer',
            'preferred_zip_codes' => 'array',
        ];
    }

    /**
     * Get the user that owns this provider profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the provider's Stripe account.
     */
    public function stripeAccount(): HasOne
    {
        return $this->hasOne(ProviderStripeAccount::class);
    }

    /**
     * Get the provider's bookings.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Get the provider's payouts.
     */
    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    /**
     * Get the provider's invitations.
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(ProviderInvitation::class);
    }

    /**
     * Update the provider's rating aggregates.
     */
    public function updateRatingAggregates(): void
    {
        $ratings = $this->user->ratingsReceived()->where('ratee_id', $this->user_id);

        $this->update([
            'average_rating' => $ratings->avg('rating') ?? 0,
            'total_ratings' => $ratings->count(),
        ]);
    }

    /**
     * Check if provider can receive payouts (Stripe onboarding complete and active).
     */
    public function canReceivePayouts(): bool
    {
        return ($this->stripeAccount?->isFullyOnboarded() ?? false) && $this->is_active;
    }

    /**
     * Check if provider is available on a given date/time.
     */
    public function isAvailableOn(\DateTime $date): bool
    {
        if (! $this->is_active) {
            return false;
        }

        // Check blackout dates
        if ($this->blackout_dates) {
            $dateString = $date->format('Y-m-d');
            if (in_array($dateString, $this->blackout_dates)) {
                return false;
            }
        }

        // If no schedule set, assume always available when active
        if (! $this->availability_schedule) {
            return true;
        }

        $dayOfWeek = strtolower($date->format('l')); // 'monday', 'tuesday', etc.

        return isset($this->availability_schedule[$dayOfWeek]) &&
               $this->availability_schedule[$dayOfWeek]['available'] === true;
    }

    /**
     * Get upcoming bookings for the provider.
     */
    public function upcomingBookings()
    {
        return $this->bookings()
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereHas('serviceRequest', function ($query) {
                $query->where('service_date', '>=', now());
            })
            ->with(['serviceRequest.shopLocation.shop'])
            ->orderBy('created_at', 'desc');
    }

    /**
     * Calculate profile completeness as a percentage and list of completed steps.
     *
     * @return array{percentage: int, steps: array<string, bool>}
     */
    public function profileCompleteness(): array
    {
        $steps = [
            'profile_created' => true,
            'bio_added' => ! empty($this->bio),
            'skills_added' => ! empty($this->skills),
            'payout_account_connected' => $this->stripeAccount?->isFullyOnboarded() ?? false,
            'availability_set' => ! empty($this->availability_schedule),
        ];

        $completed = count(array_filter($steps));
        $total = count($steps);

        return [
            'percentage' => (int) round(($completed / $total) * 100),
            'steps' => $steps,
        ];
    }

    /**
     * Get completed bookings for the provider.
     */
    public function completedBookings()
    {
        return $this->bookings()
            ->where('status', 'completed')
            ->with(['serviceRequest.shopLocation.shop'])
            ->orderBy('updated_at', 'desc');
    }
}
