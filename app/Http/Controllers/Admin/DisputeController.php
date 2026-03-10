<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dispute;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DisputeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $statusFilter = $request->query('filter', 'all');

        $disputes = Dispute::with([
            'booking.serviceRequest.shopLocation.shop.user',
            'booking.provider.user',
            'filedByUser',
        ])
            ->when($statusFilter !== 'all', fn ($q) => $q->where('status', $statusFilter))
            ->get()
            ->sortByDesc(fn (Dispute $dispute) => $dispute->priority_score)
            ->values();

        $disputes = $disputes->map(function (Dispute $dispute) {
            $booking = $dispute->booking;
            $shopUser = $booking?->serviceRequest?->shopLocation?->shop?->user;
            $providerUser = $booking?->provider?->user;
            $filedBy = $dispute->filedByUser;

            $against = $filedBy?->id === $shopUser?->id ? $providerUser : $shopUser;

            $dispute->filed_by = $filedBy
                ? ['name' => $filedBy->name, 'email' => $filedBy->email]
                : null;

            $dispute->against = $against
                ? ['name' => $against->name, 'email' => $against->email]
                : null;

            return $dispute;
        });

        $paginatedDisputes = new \Illuminate\Pagination\LengthAwarePaginator(
            items: $disputes->forPage(\Illuminate\Pagination\Paginator::resolveCurrentPage(), 15)->values(),
            total: $disputes->count(),
            perPage: 15,
            currentPage: \Illuminate\Pagination\Paginator::resolveCurrentPage(),
            options: ['path' => request()->url(), 'query' => request()->query()],
        );

        $statusCounts = [
            'open' => Dispute::where('status', 'open')->count(),
            'under_review' => Dispute::where('status', 'under_review')->count(),
            'resolved' => Dispute::where('status', 'resolved')->count(),
            'closed' => Dispute::where('status', 'closed')->count(),
        ];

        return Inertia::render('admin/Disputes', [
            'disputes' => $paginatedDisputes,
            'filter' => $statusFilter,
            'statusCounts' => $statusCounts,
        ]);
    }
}
