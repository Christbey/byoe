<?php

namespace App\Console\Commands;

use App\Mail\Shop\BookingCompletionReminder;
use App\Models\Booking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendBookingCompletionReminders extends Command
{
    protected $signature = 'emails:send-booking-completion-reminders
                            {--user= : Send to a specific user ID for testing}
                            {--test : Send test emails immediately without queueing}';

    protected $description = 'Send reminders to shops to mark bookings complete';

    public function handle()
    {
        $yesterday = now()->subDay()->startOfDay();
        $twoDaysAgo = $yesterday->copy()->subDay();

        $query = Booking::where('status', 'confirmed')
            ->whereHas('serviceRequest', function ($q) use ($twoDaysAgo, $yesterday) {
                $q->whereBetween('service_date', [$twoDaysAgo, $yesterday]);
            })
            ->with(['serviceRequest.shopLocation.shop.user', 'provider.user']);

        // Filter by specific user if testing
        if ($userId = $this->option('user')) {
            $query->whereHas('serviceRequest.shopLocation.shop.user', fn ($q) => $q->where('id', $userId));
        }

        $bookings = $query->get();

        $sent = 0;
        $isTest = $this->option('test');

        foreach ($bookings as $booking) {
            // Skip if user has opted out (unless testing)
            if (! $isTest && ! $booking->serviceRequest->shopLocation->shop->user->wantsEmail('booking_reminders')) {
                continue;
            }

            if ($isTest) {
                Mail::to($booking->serviceRequest->shopLocation->shop->user->email)->send(
                    new BookingCompletionReminder($booking)
                );
                $this->info("Test email sent to {$booking->serviceRequest->shopLocation->shop->user->email}");
            } else {
                Mail::to($booking->serviceRequest->shopLocation->shop->user->email)->queue(
                    new BookingCompletionReminder($booking)
                );
            }
            $sent++;
        }

        $this->info("Sent {$sent} booking completion reminders.");

        return Command::SUCCESS;
    }
}
