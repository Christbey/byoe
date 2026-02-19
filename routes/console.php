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
