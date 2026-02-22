<?php

namespace App\Console\Commands;

use App\Mail\Provider\DailyNearbyRequestsDigest;
use App\Models\Provider;
use App\Models\ServiceRequest;
use App\Services\GeocodingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDailyNearbyRequestsDigests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:send-daily-digests
                            {--user= : Send to a specific user ID for testing}
                            {--test : Send test emails immediately without queueing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily nearby request digests to all active providers';

    /**
     * Execute the console command.
     */
    public function handle(GeocodingService $geocodingService)
    {
        $this->info('Sending daily nearby requests digests...');

        $query = Provider::where('is_active', true)->with('user');

        // Filter by specific user if testing
        if ($userId = $this->option('user')) {
            $query->whereHas('user', fn ($q) => $q->where('id', $userId));
        }

        $providers = $query->get();

        $sent = 0;
        $isTest = $this->option('test');

        foreach ($providers as $provider) {
            // Skip if user has opted out of daily digests (unless testing)
            if (! $isTest && ! $provider->user->wantsEmail('daily_digest')) {
                continue;
            }

            // Get nearby requests for this provider
            $requests = $this->getNearbyRequests($provider, $geocodingService);

            // Only send if there are requests OR if it's been configured to send even with no results
            if ($requests->count() > 0 || config('mail.daily_digest_send_empty', false) || $isTest) {
                if ($isTest) {
                    Mail::to($provider->user->email)->send(
                        new DailyNearbyRequestsDigest($provider, $requests)
                    );
                    $this->info("Test email sent to {$provider->user->email}");
                } else {
                    Mail::to($provider->user->email)->queue(
                        new DailyNearbyRequestsDigest($provider, $requests)
                    );
                }
                $sent++;
            }
        }

        $this->info("Sent {$sent} digest emails to providers.");

        return Command::SUCCESS;
    }

    /**
     * Get nearby service requests for a provider.
     */
    private function getNearbyRequests(Provider $provider, GeocodingService $geocodingService)
    {
        $query = ServiceRequest::query()
            ->where('status', 'open')
            ->where('expires_at', '>', now())
            ->where('service_date', '>=', today())
            ->where('service_date', '<=', today()->addDays(7))
            ->with(['shopLocation.shop']);

        // Filter by provider's skills if they have any
        if (! empty($provider->skills)) {
            $query->where(function ($q) use ($provider) {
                foreach ($provider->skills as $skill) {
                    $q->orWhereJsonContains('skills_required', $skill);
                }
            });
        }

        // Filter by location if provider has zip codes
        if (! empty($provider->preferred_zip_codes)) {
            $firstZip = $provider->preferred_zip_codes[0];
            $coords = $geocodingService->geocodeZip($firstZip);

            if ($coords) {
                $radiusMiles = $provider->service_area_max_miles ?: 25;
                $latDelta = $radiusMiles / 69.0;
                $lngDelta = $radiusMiles / (69.0 * cos(deg2rad($coords['lat'])));

                $query->whereHas('shopLocation', function ($q) use ($coords, $latDelta, $lngDelta) {
                    $q->whereBetween('latitude', [$coords['lat'] - $latDelta, $coords['lat'] + $latDelta])
                        ->whereBetween('longitude', [$coords['lng'] - $lngDelta, $coords['lng'] + $lngDelta]);
                });
            }
        }

        return $query->orderBy('service_date')->limit(10)->get();
    }
}
