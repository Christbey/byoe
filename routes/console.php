<?php

use App\Jobs\ExpireServiceRequestsJob;
use App\Jobs\ProcessPayoutJob;
use App\Models\Payout;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new ExpireServiceRequestsJob)->hourly();

Schedule::call(function () {
    Payout::where('status', 'scheduled')
        ->where('scheduled_for', '<=', now())
        ->each(fn (Payout $payout) => ProcessPayoutJob::dispatch($payout));
})->everyFifteenMinutes()->name('process-due-payouts')->withoutOverlapping();

// Email Notifications
Schedule::command('emails:send-daily-digests')->dailyAt('07:00')->name('daily-request-digests');
Schedule::command('emails:send-upcoming-booking-reminders')->dailyAt('18:00')->name('upcoming-booking-reminders');
Schedule::command('emails:send-booking-completion-reminders')->dailyAt('10:00')->name('booking-completion-reminders');
Schedule::command('emails:send-weekly-summaries')->weekly()->mondays()->at('08:00')->name('weekly-summaries');
Schedule::command('emails:send-request-expiring-alerts')->everyThreeHours()->name('request-expiring-alerts');
