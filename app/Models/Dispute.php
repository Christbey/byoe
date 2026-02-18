<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dispute extends Model
{
    /** @use HasFactory<\Database\Factories\DisputeFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'booking_id',
        'filed_by_user_id',
        'dispute_type',
        'description',
        'status',
        'resolved_by_user_id',
        'resolution_notes',
        'resolved_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * Get the booking for this dispute.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the user who filed the dispute.
     */
    public function filedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'filed_by_user_id');
    }

    /**
     * Get the user who resolved the dispute.
     */
    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    /**
     * Check if the dispute is open.
     */
    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    /**
     * Check if the dispute is under review.
     */
    public function isUnderReview(): bool
    {
        return $this->status === 'under_review';
    }

    /**
     * Check if the dispute is resolved.
     */
    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    /**
     * Check if the dispute is closed.
     */
    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * Mark the dispute as under review.
     */
    public function markAsUnderReview(): void
    {
        $this->update(['status' => 'under_review']);
    }

    /**
     * Resolve the dispute.
     */
    public function resolve(User $resolver, string $notes): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_by_user_id' => $resolver->id,
            'resolution_notes' => $notes,
            'resolved_at' => now(),
        ]);
    }

    /**
     * Close the dispute.
     */
    public function close(): void
    {
        $this->update(['status' => 'closed']);
    }

    /**
     * Check if the dispute is payment-related.
     */
    public function isPaymentDispute(): bool
    {
        return $this->dispute_type === 'payment';
    }

    /**
     * Check if the dispute is service quality-related.
     */
    public function isServiceQualityDispute(): bool
    {
        return $this->dispute_type === 'service_quality';
    }

    /**
     * Check if the dispute is cancellation-related.
     */
    public function isCancellationDispute(): bool
    {
        return $this->dispute_type === 'cancellation';
    }

    /**
     * Check if the dispute is no-show related.
     */
    public function isNoShowDispute(): bool
    {
        return $this->dispute_type === 'no_show';
    }
}
