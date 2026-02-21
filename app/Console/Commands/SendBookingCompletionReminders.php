<?php

namespace App\Console\Commands;

use App\Mail\Shop\BookingCompletionReminder;
use App\Models\Booking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendBookingCompletionReminders extends Command
{
    protected $signature = 'emails:send-booking-completion-reminders';

    protected $description = 'Send reminders to shops to mark bookings complete';

    public function handle()
    {
        $yesterday = now()->subDay()->startOfDay();
        $twoDaysAgo = $yesterday->copy()->subDay();

        $bookings = Booking::where('status', 'confirmed')
            ->whereHas('serviceRequest', function ($q) use ($twoDaysAgo, $yesterday) {
                $q->whereBetween('service_date', [$twoDaysAgo, $yesterday]);
            })
            ->with(['serviceRequest.shopLocation.shop.user', 'provider.user'])
            ->get();

        $sent = 0;

        foreach ($bookings as $booking) {
            if ($booking->serviceRequest->shopLocation->shop->user->wantsEmail('booking_reminders')) {
                Mail::to($booking->serviceRequest->shopLocation->shop->user->email)->queue(
                    new BookingCompletionReminder($booking)
                );
                $sent++;
            }
        }

        $this->info("Sent {$sent} booking completion reminders.");

        return Command::SUCCESS;
    }
}
