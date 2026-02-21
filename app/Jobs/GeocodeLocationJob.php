<?php

namespace App\Jobs;

use App\Models\ShopLocation;
use App\Services\GeocodingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GeocodeLocationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ShopLocation $shopLocation
    ) {}

    /**
     * Execute the job.
     */
    public function handle(GeocodingService $geocodingService): void
    {
        try {
            $success = $geocodingService->geocodeLocation($this->shopLocation);

            if (! $success) {
                Log::warning('Failed to geocode location', [
                    'location_id' => $this->shopLocation->id,
                    'address' => $this->shopLocation->fullAddress(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Geocoding job failed', [
                'location_id' => $this->shopLocation->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
