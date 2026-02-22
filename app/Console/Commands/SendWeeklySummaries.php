<?php

namespace App\Console\Commands;

use App\Mail\Provider\WeeklyEarningsSummary;
use App\Mail\Shop\WeeklyBookingSummary;
use App\Models\Provider;
use App\Models\Shop;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendWeeklySummaries extends Command
{
    protected $signature = 'emails:send-weekly-summaries
                            {--user= : Send to a specific user ID for testing}
                            {--test : Send test emails immediately without queueing}';

    protected $description = 'Send weekly summaries to providers and shops';

    public function handle()
    {
        $this->sendProviderSummaries();
        $this->sendShopSummaries();

        return Command::SUCCESS;
    }

    private function sendProviderSummaries()
    {
        $query = Provider::where('is_active', true)->with('user');

        // Filter by specific user if testing
        if ($userId = $this->option('user')) {
            $query->whereHas('user', fn ($q) => $q->where('id', $userId));
        }

        $providers = $query->get();
        $sent = 0;
        $isTest = $this->option('test');

        foreach ($providers as $provider) {
            $weeklyEarnings = $provider->payouts()
                ->where('status', 'completed')
                ->where('processed_at', '>=', now()->subWeek())
                ->sum('amount');

            $completedBookings = $provider->bookings()
                ->where('status', 'completed')
                ->where('completed_at', '>=', now()->subWeek())
                ->count();

            $averageRating = $provider->total_ratings > 0 ? $provider->average_rating : 0;

            // Send if there's activity OR if testing (ignore preferences in test mode)
            if (($completedBookings > 0 || $weeklyEarnings > 0 || $isTest) && ($isTest || $provider->user->wantsEmail('weekly_summary'))) {
                if ($isTest) {
                    Mail::to($provider->user->email)->send(
                        new WeeklyEarningsSummary($provider, $weeklyEarnings, $completedBookings, $averageRating)
                    );
                    $this->info("Test email sent to {$provider->user->email}");
                } else {
                    Mail::to($provider->user->email)->queue(
                        new WeeklyEarningsSummary($provider, $weeklyEarnings, $completedBookings, $averageRating)
                    );
                }
                $sent++;
            }
        }

        $this->info("Sent {$sent} provider weekly summaries.");
    }

    private function sendShopSummaries()
    {
        $query = Shop::where('status', 'active')->with('user');

        // Filter by specific user if testing
        if ($userId = $this->option('user')) {
            $query->whereHas('user', fn ($q) => $q->where('id', $userId));
        }

        $shops = $query->get();
        $sent = 0;
        $isTest = $this->option('test');

        foreach ($shops as $shop) {
            $upcomingBookings = $shop->bookings()
                ->where('status', 'confirmed')
                ->whereHas('serviceRequest', fn ($q) => $q->where('service_date', '>=', today()))
                ->with('provider.user', 'serviceRequest')
                ->get();

            $completedBookings = $shop->bookings()
                ->where('status', 'completed')
                ->where('completed_at', '>=', now()->subWeek())
                ->with('serviceRequest')
                ->get();

            $totalSpent = $completedBookings->sum(fn ($b) => $b->serviceRequest->price);

            // Send if there's activity OR if testing (ignore preferences in test mode)
            if (($upcomingBookings->count() > 0 || $completedBookings->count() > 0 || $isTest) && ($isTest || $shop->user->wantsEmail('weekly_summary'))) {
                if ($isTest) {
                    Mail::to($shop->user->email)->send(
                        new WeeklyBookingSummary($shop, $upcomingBookings, $completedBookings, $totalSpent)
                    );
                    $this->info("Test email sent to {$shop->user->email}");
                } else {
                    Mail::to($shop->user->email)->queue(
                        new WeeklyBookingSummary($shop, $upcomingBookings, $completedBookings, $totalSpent)
                    );
                }
                $sent++;
            }
        }

        $this->info("Sent {$sent} shop weekly summaries.");
    }
}
