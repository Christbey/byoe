<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderStripeAccount extends Model
{
    /** @use HasFactory<\Database\Factories\ProviderStripeAccountFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'provider_id',
        'stripe_account_id',
        'details_submitted',
        'charges_enabled',
        'payouts_enabled',
        'onboarding_completed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'details_submitted' => 'boolean',
            'charges_enabled' => 'boolean',
            'payouts_enabled' => 'boolean',
            'onboarding_completed_at' => 'datetime',
        ];
    }

    /**
     * Get the provider that owns this Stripe account.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    /**
     * Check if the account is fully onboarded.
     */
    public function isFullyOnboarded(): bool
    {
        return $this->details_submitted &&
               $this->charges_enabled &&
               $this->payouts_enabled;
    }
}
