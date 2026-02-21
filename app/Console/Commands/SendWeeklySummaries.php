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
    protected $signature = 'emails:send-weekly-summaries';

    protected $description = 'Send weekly summaries to providers and shops';

    public function handle()
    {
        $this->sendProviderSummaries();
        $this->sendShopSummaries();

        return Command::SUCCESS;
    }

    private function sendProviderSummaries()
    {
        $providers = Provider::where('is_active', true)->with('user')->get();
        $sent = 0;

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

            if ($completedBookings > 0 || $weeklyEarnings > 0) {
                Mail::to($provider->user->email)->queue(
                    new WeeklyEarningsSummary($provider, $weeklyEarnings, $completedBookings, $averageRating)
                );
                $sent++;
            }
        }

        $this->info("Sent {$sent} provider weekly summaries.");
    }

    private function sendShopSummaries()
    {
        $shops = Shop::where('status', 'active')->with('user')->get();
        $sent = 0;

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

            if ($upcomingBookings->count() > 0 || $completedBookings->count() > 0) {
                Mail::to($shop->user->email)->queue(
                    new WeeklyBookingSummary($shop, $upcomingBookings, $completedBookings, $totalSpent)
                );
                $sent++;
            }
        }

        $this->info("Sent {$sent} shop weekly summaries.");
    }
}
