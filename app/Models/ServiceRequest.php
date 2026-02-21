<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ServiceRequest extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceRequestFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    /**
     * @var list<string>
     */
    protected $appends = ['status_label', 'status_variant'];

    protected $fillable = [
        'shop_location_id',
        'title',
        'description',
        'skills_required',
        'service_date',
        'start_time',
        'end_time',
        'price',
        'status',
        'expires_at',
        'stripe_payment_intent_id',
        'stripe_client_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'skills_required' => 'array',
            'service_date' => 'date',
            'price' => 'float',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Get the shop location for this service request.
     */
    public function shopLocation(): BelongsTo
    {
        return $this->belongsTo(ShopLocation::class);
    }

    /**
     * Get the booking for this service request.
     */
    public function booking(): HasOne
    {
        return $this->hasOne(Booking::class);
    }

    /**
     * Get the provider invitations for this service request.
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(ProviderInvitation::class);
    }

    /**
     * Check if the service request is open.
     */
    /**
     * Human-readable label for the service request status.
     * If the request is filled and its booking is completed, surfaces "Completed".
     */
    public function getStatusLabelAttribute(): string
    {
        if ($this->status === 'filled' && $this->relationLoaded('booking') && $this->booking?->status === 'completed') {
            return 'Completed';
        }

        return match ($this->status) {
            'pending_payment' => 'Payment Required',
            'open' => 'Open',
            'filled' => 'Booked',
            'expired' => 'Expired',
            'cancelled' => 'Cancelled',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    /**
     * Badge variant for the service request status.
     */
    public function getStatusVariantAttribute(): string
    {
        if ($this->status === 'filled' && $this->relationLoaded('booking') && $this->booking?->status === 'completed') {
            return 'success';
        }

        return match ($this->status) {
            'pending_payment' => 'warning',
            'open' => 'info',
            'expired' => 'destructive',
            default => 'outline',
        };
    }

    public function isPendingPayment(): bool
    {
        return $this->status === 'pending_payment';
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    /**
     * Check if the service request is filled.
     */
    public function isFilled(): bool
    {
        return $this->status === 'filled';
    }

    /**
     * Check if the service request is expired.
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired' || $this->expires_at->isPast();
    }

    /**
     * Check if the service request is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Mark the service request as filled.
     */
    public function markAsFilled(): void
    {
        $this->update(['status' => 'filled']);
    }

    /**
     * Mark the service request as expired.
     */
    public function markAsExpired(): void
    {
        $this->update(['status' => 'expired']);
    }

    /**
     * Mark the service request as cancelled.
     */
    public function markAsCancelled(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Get the shop through the shop location.
     */
    public function shop(): BelongsTo
    {
        return $this->shopLocation->shop();
    }
}
