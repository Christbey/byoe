<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AvailableRequestsController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        // Get filter from query params
        $filter = $request->query('filter', 'all');

        // Build query for available service requests
        $query = ServiceRequest::with(['shopLocation.shop'])
            ->where('status', 'open')
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'desc');

        // Apply filters
        switch ($filter) {
            case 'today':
                $query->whereDate('service_date', today());
                break;
            case 'week':
                $query->whereBetween('service_date', [now(), now()->addWeek()]);
                break;
            case 'nearby':
                // TODO: Implement geolocation filtering
                // For now, just show all
                break;
        }

        // Paginate results
        $requests = $query->paginate(15);

        return Inertia::render('provider/AvailableRequests', [
            'requests' => $requests,
            'filter' => $filter,
        ]);
    }
}
