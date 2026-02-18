<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExpireServiceRequestsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Find all open service requests that have expired
        $expiredRequests = \App\Models\ServiceRequest::query()
            ->where('status', 'open')
            ->where('expires_at', '<=', now())
            ->get();

        // Mark each as expired
        foreach ($expiredRequests as $request) {
            $request->markAsExpired();
        }

        \Illuminate\Support\Facades\Log::info('Expired service requests processed', [
            'count' => $expiredRequests->count(),
        ]);
    }
}
