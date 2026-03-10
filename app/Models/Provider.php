<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Provider extends Model
{
    /** @use HasFactory<\Database\Factories\ProviderFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $appends = ['trust_tier', 'trust_action_items'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'industry_id',
        'bio',
        'skills',
        'years_experience',
        'average_rating',
        'total_ratings',
        'completed_bookings',
        'is_active',
        'vetting_status',
        'background_check_status',
        'identity_verified_at',
        'vetting_completed_at',
        'last_reviewed_at',
        'last_reviewed_by_user_id',
        'trust_score',
        'reliability_score',
        'cancellation_count',
        'cancellation_rate',
        'no_show_count',
        'dispute_count',
        'completed_without_issue_count',
        'trust_notes',
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
            'identity_verified_at' => 'datetime',
            'vetting_completed_at' => 'datetime',
            'last_reviewed_at' => 'datetime',
            'trust_score' => 'integer',
            'reliability_score' => 'integer',
            'cancellation_count' => 'integer',
            'cancellation_rate' => 'float',
            'no_show_count' => 'integer',
            'dispute_count' => 'integer',
            'completed_without_issue_count' => 'integer',
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
     * Get the provider's industry.
     */
    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    /**
     * Get the provider's Stripe account.
     */
    public function stripeAccount(): HasOne
    {
        return $this->hasOne(ProviderStripeAccount::class);
    }

    /**
     * Get the admin user who last reviewed this provider.
     */
    public function lastReviewedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_reviewed_by_user_id');
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
     * Get the merged list of skill names available to this provider.
     *
     * Combines industry-default skills with the provider's own skills,
     * deduplicating the result. Safe to call without pre-loading relations.
     *
     * @return list<string>
     */
    public function availableSkills(): array
    {
        $this->loadMissing('industry.skills');

        $industrySkills = $this->industry?->skills->pluck('name')->toArray() ?? [];

        if (! empty($this->skills)) {
            $skills = array_values(array_unique(array_merge($industrySkills, $this->skills)));
        } else {
            $skills = $industrySkills;
        }

        return $skills;
    }

    /**
     * Get the industry templates available to this provider.
     *
     * @return array<int, array<string, mixed>>
     */
    public function availableTemplates(): array
    {
        $this->loadMissing('industry.templates');

        return $this->industry?->templates->toArray() ?? [];
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
     * Recalculate provider trust and reliability metrics from bookings, ratings, and disputes.
     */
    public function refreshTrustMetrics(): void
    {
        $this->loadMissing('stripeAccount');

        $completedCount = $this->bookings()->where('status', 'completed')->count();
        $cancelledBookings = $this->bookings()->where('status', 'cancelled')->get(['cancellation_reason']);
        $cancellationCount = $cancelledBookings->count();
        $noShowCount = $cancelledBookings
            ->filter(fn (Booking $booking) => Str::contains(Str::lower((string) $booking->cancellation_reason), 'no-show'))
            ->count();

        $disputeCount = Dispute::whereHas('booking', fn ($query) => $query->where('provider_id', $this->id))->count();
        $completedWithoutIssueCount = $this->bookings()
            ->where('status', 'completed')
            ->whereDoesntHave('disputes')
            ->count();

        $trackedBookings = max($completedCount + $cancellationCount, 1);
        $cancellationRate = round(($cancellationCount / $trackedBookings) * 100, 2);

        $openDisputes = Dispute::whereHas('booking', fn ($query) => $query->where('provider_id', $this->id))
            ->whereIn('status', ['open', 'under_review'])
            ->count();

        $ratingComponent = min(100, (float) (($this->average_rating ?? 0) / 5) * 100);
        $experienceComponent = min(100, ($completedCount / 25) * 100);
        $verificationBonus = 0;

        if ($this->identity_verified_at) {
            $verificationBonus += 8;
        }

        if ($this->background_check_status === 'clear') {
            $verificationBonus += 10;
        }

        if ($this->vetting_status === 'approved') {
            $verificationBonus += 12;
        }

        if ($this->stripeAccount?->isFullyOnboarded()) {
            $verificationBonus += 5;
        }

        $reliabilityScore = max(
            0,
            min(
                100,
                (int) round(
                    100
                    - ($cancellationRate * 1.35)
                    - ($noShowCount * 14)
                    - ($openDisputes * 8)
                )
            )
        );

        $trustScore = max(
            0,
            min(
                100,
                (int) round(
                    ($ratingComponent * 0.45)
                    + ($reliabilityScore * 0.35)
                    + ($experienceComponent * 0.20)
                    + $verificationBonus
                )
            )
        );

        $this->update([
            'completed_bookings' => $completedCount,
            'cancellation_count' => $cancellationCount,
            'cancellation_rate' => $cancellationRate,
            'no_show_count' => $noShowCount,
            'dispute_count' => $disputeCount,
            'completed_without_issue_count' => $completedWithoutIssueCount,
            'reliability_score' => $reliabilityScore,
            'trust_score' => $trustScore,
        ]);
    }

    /**
     * Review the provider and update moderation fields.
     */
    public function review(User $reviewer, string $vettingStatus, string $backgroundCheckStatus, ?string $notes = null): void
    {
        $wasApproved = $this->vetting_status === 'approved';

        $this->update([
            'vetting_status' => $vettingStatus,
            'background_check_status' => $backgroundCheckStatus,
            'trust_notes' => $notes,
            'last_reviewed_by_user_id' => $reviewer->id,
            'last_reviewed_at' => now(),
            'vetting_completed_at' => $vettingStatus === 'pending_review' ? null : now(),
            'is_active' => $vettingStatus !== 'suspended',
            'identity_verified_at' => $vettingStatus === 'approved' && ! $this->identity_verified_at
                ? now()
                : $this->identity_verified_at,
        ]);

        if (! $wasApproved && $vettingStatus === 'approved') {
            $this->refreshTrustMetrics();
        }
    }

    /**
     * Check if provider can receive payouts (Stripe onboarding complete and active).
     */
    public function canReceivePayouts(): bool
    {
        return ($this->stripeAccount?->isFullyOnboarded() ?? false)
            && $this->is_active
            && $this->vetting_status === 'approved';
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
            'industry_selected' => ! empty($this->industry_id),
            'bio_added' => ! empty($this->bio),
            'skills_added' => ! empty($this->skills),
            'payout_account_connected' => $this->stripeAccount?->isFullyOnboarded() ?? false,
            'availability_set' => ! empty($this->availability_schedule),
            'trust_review_complete' => $this->vetting_status === 'approved',
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

    /**
     * Whether the provider still requires manual review.
     */
    public function needsReview(): bool
    {
        return $this->vetting_status === 'pending_review';
    }

    /**
     * Whether the provider is currently approved.
     */
    public function isApproved(): bool
    {
        return $this->vetting_status === 'approved';
    }

    /**
     * Human-friendly trust tier derived from the trust score.
     */
    public function trustTier(): string
    {
        return match (true) {
            $this->trust_score >= 90 => 'elite',
            $this->trust_score >= 75 => 'trusted',
            $this->trust_score >= 55 => 'standard',
            default => 'at_risk',
        };
    }

    public function getTrustTierAttribute(): string
    {
        return $this->trustTier();
    }

    /**
     * Provider-facing remediation checklist derived from trust state.
     *
     * @return array<int, array{title: string, detail: string, action_label: string, action_href: string, severity: string}>
     */
    public function trustActionItems(): array
    {
        $items = [];

        if ($this->vetting_status === 'pending_review') {
            $items[] = [
                'title' => 'Finish trust review',
                'detail' => 'Your profile is waiting for manual approval before payouts and booking confidence unlock fully.',
                'action_label' => 'Review profile',
                'action_href' => route('settings.provider'),
                'severity' => 'warning',
            ];
        }

        if (in_array($this->background_check_status, ['pending', 'expired'], true)) {
            $items[] = [
                'title' => 'Refresh verification details',
                'detail' => 'Background check information is incomplete or outdated. Update your profile so ops can finish review.',
                'action_label' => 'Update details',
                'action_href' => route('provider.profile.edit'),
                'severity' => 'warning',
            ];
        }

        if (! ($this->stripeAccount?->isFullyOnboarded() ?? false)) {
            $items[] = [
                'title' => 'Complete payout setup',
                'detail' => 'Connect Stripe so completed bookings can be paid out and your account is eligible to accept work.',
                'action_label' => 'Set up Stripe',
                'action_href' => route('provider.stripe-setup'),
                'severity' => 'warning',
            ];
        }

        if (($this->cancellation_rate ?? 0) >= 15 || ($this->no_show_count ?? 0) > 0) {
            $items[] = [
                'title' => 'Stabilize reliability',
                'detail' => 'Your cancellation or no-show history is affecting trust. Tighten availability and only accept shifts you can confidently complete.',
                'action_label' => 'Manage availability',
                'action_href' => route('provider.profile'),
                'severity' => 'danger',
            ];
        }

        if (($this->dispute_count ?? 0) > 0) {
            $items[] = [
                'title' => 'Resolve active issues',
                'detail' => 'Disputes tied to your bookings are reducing trust. Review recent bookings and respond quickly when support reaches out.',
                'action_label' => 'Review bookings',
                'action_href' => route('provider.bookings.index'),
                'severity' => 'danger',
            ];
        }

        if (($this->average_rating ?? 0) > 0 && $this->average_rating < 4.2) {
            $items[] = [
                'title' => 'Improve service quality',
                'detail' => 'Recent ratings are below target. Focus on punctuality, communication, and shift follow-through.',
                'action_label' => 'See feedback',
                'action_href' => route('settings.provider'),
                'severity' => 'warning',
            ];
        }

        return $items;
    }

    public function getTrustActionItemsAttribute(): array
    {
        return $this->trustActionItems();
    }
}
