<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payout extends Model
{
    /** @use HasFactory<\Database\Factories\PayoutFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'booking_id',
        'provider_id',
        'stripe_transfer_id',
        'amount',
        'currency',
        'status',
        'scheduled_for',
        'paid_at',
        'failure_message',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'scheduled_for' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Get the booking for this payout.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the provider for this payout.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    /**
     * Check if the payout is scheduled.
     */
    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    /**
     * Check if the payout is processing.
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Check if the payout is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if the payout failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Mark the payout as processing.
     */
    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    /**
     * Mark the payout as paid.
     */
    public function markAsPaid(string $stripeTransferId): void
    {
        $this->update([
            'status' => 'paid',
            'stripe_transfer_id' => $stripeTransferId,
            'paid_at' => now(),
        ]);
    }

    /**
     * Mark the payout as failed.
     */
    public function markAsFailed(string $message): void
    {
        $this->update([
            'status' => 'failed',
            'failure_message' => $message,
        ]);
    }

    /**
     * Get the formatted amount with currency.
     */
    public function formattedAmount(): string
    {
        return '$'.number_format($this->amount, 2);
    }

    /**
     * Check if the payout is due (scheduled time has passed).
     */
    public function isDue(): bool
    {
        return $this->isScheduled() && $this->scheduled_for->isPast();
    }
}
