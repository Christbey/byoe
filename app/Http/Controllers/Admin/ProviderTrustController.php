<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProviderTrustController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $status = $request->query('status', 'all');
        $search = $request->query('search');
        $risk = $request->query('risk', 'all');

        $providers = Provider::with(['user', 'industry', 'stripeAccount', 'lastReviewedByUser'])
            ->when($search, function ($query) use ($search) {
                $query->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($status !== 'all', fn ($query) => $query->where('vetting_status', $status))
            ->when($risk === 'attention', fn ($query) => $query->where(function ($riskQuery) {
                $riskQuery->where('trust_score', '<', 60)
                    ->orWhere('reliability_score', '<', 70)
                    ->orWhere('background_check_status', 'flagged')
                    ->orWhere('vetting_status', 'needs_attention');
            }))
            ->orderByRaw("
                case
                    when vetting_status = 'pending_review' then 0
                    when vetting_status = 'needs_attention' then 1
                    when vetting_status = 'suspended' then 2
                    else 3
                end
            ")
            ->orderBy('trust_score')
            ->paginate(15)
            ->withQueryString();

        $counts = [
            'pending_review' => Provider::where('vetting_status', 'pending_review')->count(),
            'approved' => Provider::where('vetting_status', 'approved')->count(),
            'needs_attention' => Provider::where('vetting_status', 'needs_attention')->count(),
            'suspended' => Provider::where('vetting_status', 'suspended')->count(),
        ];

        return Inertia::render('admin/Providers', [
            'providers' => $providers,
            'filters' => [
                'status' => $status,
                'search' => $search,
                'risk' => $risk,
            ],
            'counts' => $counts,
        ]);
    }
}
