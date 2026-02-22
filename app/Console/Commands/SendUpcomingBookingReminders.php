<?php

namespace App\Console\Commands;

use App\Mail\Provider\UpcomingBookingReminder;
use App\Models\Booking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendUpcomingBookingReminders extends Command
{
    protected $signature = 'emails:send-upcoming-booking-reminders
                            {--user= : Send to a specific user ID for testing}
                            {--test : Send test emails immediately without queueing}';

    protected $description = 'Send reminders for bookings starting in 24 hours';

    public function handle()
    {
        $tomorrow = now()->addDay()->startOfDay();
        $dayAfter = $tomorrow->copy()->endOfDay();

        $query = Booking::where('status', 'confirmed')
            ->whereHas('serviceRequest', function ($q) use ($tomorrow, $dayAfter) {
                $q->whereBetween('service_date', [$tomorrow, $dayAfter]);
            })
            ->with(['provider.user', 'serviceRequest.shopLocation.shop']);

        // Filter by specific user if testing
        if ($userId = $this->option('user')) {
            $query->whereHas('provider.user', fn ($q) => $q->where('id', $userId));
        }

        $bookings = $query->get();

        $sent = 0;
        $isTest = $this->option('test');

        foreach ($bookings as $booking) {
            // Skip if user has opted out (unless testing)
            if (! $isTest && ! $booking->provider->user->wantsEmail('booking_reminders')) {
                continue;
            }

            if ($isTest) {
                Mail::to($booking->provider->user->email)->send(
                    new UpcomingBookingReminder($booking)
                );
                $this->info("Test email sent to {$booking->provider->user->email}");
            } else {
                Mail::to($booking->provider->user->email)->queue(
                    new UpcomingBookingReminder($booking)
                );
            }
            $sent++;
        }

        $this->info("Sent {$sent} upcoming booking reminders.");

        return Command::SUCCESS;
    }
}
