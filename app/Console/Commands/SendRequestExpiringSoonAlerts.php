<?php

namespace App\Console\Commands;

use App\Mail\Shop\RequestExpiringSoon;
use App\Models\ServiceRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendRequestExpiringSoonAlerts extends Command
{
    protected $signature = 'emails:send-request-expiring-alerts
                            {--user= : Send to a specific user ID for testing}
                            {--test : Send test emails immediately without queueing}';

    protected $description = 'Send alerts for service requests expiring soon without accepts';

    public function handle()
    {
        $sixHoursFromNow = now()->addHours(6);

        $query = ServiceRequest::where('status', 'open')
            ->whereBetween('expires_at', [now(), $sixHoursFromNow])
            ->with('shopLocation.shop.user');

        $isTest = $this->option('test');

        // Only check for already-sent alerts in production mode
        if (! $isTest) {
            $query->whereNull('expiring_alert_sent_at');
        }

        // Filter by specific user if testing
        if ($userId = $this->option('user')) {
            $query->whereHas('shopLocation.shop.user', fn ($q) => $q->where('id', $userId));
        }

        $requests = $query->get();

        $sent = 0;

        foreach ($requests as $request) {
            // Skip if user has opted out (unless testing)
            if (! $isTest && ! $request->shopLocation->shop->user->wantsEmail('booking_reminders')) {
                continue;
            }

            if ($isTest) {
                Mail::to($request->shopLocation->shop->user->email)->send(
                    new RequestExpiringSoon($request)
                );
                $this->info("Test email sent to {$request->shopLocation->shop->user->email}");
            } else {
                Mail::to($request->shopLocation->shop->user->email)->queue(
                    new RequestExpiringSoon($request)
                );

                $request->expiring_alert_sent_at = now();
                $request->save();
            }

            $sent++;
        }

        $this->info("Sent {$sent} request expiring alerts.");

        return Command::SUCCESS;
    }
}
