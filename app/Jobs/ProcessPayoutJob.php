<?php

namespace App\Jobs;

use App\Models\Payout;
use App\Services\StripeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessPayoutJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public Payout $payout
    ) {}

    public function handle(StripeService $stripeService): void
    {
        if (! $this->payout->isScheduled()) {
            Log::info('Skipping payout — not in scheduled state', [
                'payout_id' => $this->payout->id,
                'status' => $this->payout->status,
            ]);

            return;
        }

        $stripeService->processPayout($this->payout);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessPayoutJob permanently failed', [
            'payout_id' => $this->payout->id,
            'error' => $exception->getMessage(),
        ]);

        $this->payout->markAsFailed($exception->getMessage());
    }
}
