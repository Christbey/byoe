<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dispute;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DisputeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        $statusFilter = $request->query('status', 'all');

        $query = Dispute::with([
            'booking.serviceRequest.shopLocation.shop',
            'booking.provider.user',
        ])
            ->when($statusFilter !== 'all', function ($query) use ($statusFilter) {
                $query->where('status', $statusFilter);
            })
            ->orderBy('created_at', 'desc');

        $disputes = $query->paginate(15);

        // Count by status
        $statusCounts = [
            'open' => Dispute::where('status', 'open')->count(),
            'investigating' => Dispute::where('status', 'investigating')->count(),
            'resolved' => Dispute::where('status', 'resolved')->count(),
            'closed' => Dispute::where('status', 'closed')->count(),
        ];

        return Inertia::render('admin/Disputes', [
            'disputes' => $disputes,
            'statusFilter' => $statusFilter,
            'statusCounts' => $statusCounts,
        ]);
    }
}
