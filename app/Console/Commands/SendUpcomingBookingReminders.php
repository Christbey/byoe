<?php

namespace App\Console\Commands;

use App\Mail\Provider\UpcomingBookingReminder;
use App\Models\Booking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendUpcomingBookingReminders extends Command
{
    protected $signature = 'emails:send-upcoming-booking-reminders';

    protected $description = 'Send reminders for bookings starting in 24 hours';

    public function handle()
    {
        $tomorrow = now()->addDay()->startOfDay();
        $dayAfter = $tomorrow->copy()->endOfDay();

        $bookings = Booking::where('status', 'confirmed')
            ->whereHas('serviceRequest', function ($q) use ($tomorrow, $dayAfter) {
                $q->whereBetween('service_date', [$tomorrow, $dayAfter]);
            })
            ->with(['provider.user', 'serviceRequest.shopLocation.shop'])
            ->get();

        $sent = 0;

        foreach ($bookings as $booking) {
            Mail::to($booking->provider->user->email)->queue(
                new UpcomingBookingReminder($booking)
            );
            $sent++;
        }

        $this->info("Sent {$sent} upcoming booking reminders.");

        return Command::SUCCESS;
    }
}
