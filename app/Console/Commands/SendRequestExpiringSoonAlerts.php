<?php

namespace App\Console\Commands;

use App\Mail\Shop\RequestExpiringSoon;
use App\Models\ServiceRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendRequestExpiringSoonAlerts extends Command
{
    protected $signature = 'emails:send-request-expiring-alerts';

    protected $description = 'Send alerts for service requests expiring soon without accepts';

    public function handle()
    {
        $sixHoursFromNow = now()->addHours(6);

        $requests = ServiceRequest::where('status', 'open')
            ->whereBetween('expires_at', [now(), $sixHoursFromNow])
            ->with('shopLocation.shop.user')
            ->get();

        $sent = 0;

        foreach ($requests as $request) {
            if ($request->shopLocation->shop->user->wantsEmail('booking_reminders')) {
                Mail::to($request->shopLocation->shop->user->email)->queue(
                    new RequestExpiringSoon($request)
                );
                $sent++;
            }
        }

        $this->info("Sent {$sent} request expiring alerts.");

        return Command::SUCCESS;
    }
}
