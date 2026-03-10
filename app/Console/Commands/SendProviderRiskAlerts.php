<?php

namespace App\Console\Commands;

use App\Mail\Admin\ProviderRiskDigest;
use App\Models\Dispute;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendProviderRiskAlerts extends Command
{
    protected $signature = 'emails:send-provider-risk-alerts
                            {--user= : Send to a specific admin/support/ops user ID for testing}
                            {--test : Send immediately instead of queueing}';

    protected $description = 'Send a provider risk digest to admin, support, and ops users';

    public function handle(): int
    {
        $providers = Provider::query()
            ->with('user')
            ->where(function ($query) {
                $query->where('vetting_status', 'pending_review')
                    ->orWhere('trust_score', '<', 60)
                    ->orWhere('reliability_score', '<', 75)
                    ->orWhere('background_check_status', 'flagged')
                    ->orWhere('dispute_count', '>', 0)
                    ->orWhere('no_show_count', '>', 0);
            })
            ->orderBy('trust_score')
            ->limit(20)
            ->get();

        if ($providers->isEmpty() && ! $this->option('test')) {
            $this->info('No provider risk alerts to send.');

            return self::SUCCESS;
        }

        $pendingReviewCount = Provider::where('vetting_status', 'pending_review')->count();
        $openDisputeCount = Dispute::whereIn('status', ['open', 'under_review'])->count();

        $recipients = User::query()
            ->when($this->option('user'), fn ($query, $userId) => $query->where('id', $userId))
            ->role(['admin', 'support', 'ops'])
            ->get();

        $sent = 0;

        foreach ($recipients as $recipient) {
            $mail = new ProviderRiskDigest($providers, $pendingReviewCount, $openDisputeCount);

            if ($this->option('test')) {
                Mail::to($recipient->email)->send($mail);
                $this->info("Test provider risk digest sent to {$recipient->email}");
            } else {
                Mail::to($recipient->email)->queue($mail);
            }

            $sent++;
        }

        $this->info("Sent {$sent} provider risk digest email(s).");

        return self::SUCCESS;
    }
}
