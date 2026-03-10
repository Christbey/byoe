<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Provider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewProviderTrustController extends Controller
{
    public function __invoke(Request $request, Provider $provider): RedirectResponse
    {
        $validated = $request->validate([
            'vetting_status' => ['required', 'in:pending_review,approved,needs_attention,suspended'],
            'background_check_status' => ['required', 'in:pending,clear,flagged,expired'],
            'trust_notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $oldValues = $provider->only([
            'vetting_status',
            'background_check_status',
            'trust_notes',
            'is_active',
            'last_reviewed_at',
            'last_reviewed_by_user_id',
        ]);

        $provider->review(
            reviewer: $request->user(),
            vettingStatus: $validated['vetting_status'],
            backgroundCheckStatus: $validated['background_check_status'],
            notes: $validated['trust_notes'] ?? null,
        );

        $provider->refreshTrustMetrics();

        AuditLog::logAction(
            $request->user(),
            'provider_trust_reviewed',
            $provider,
            $oldValues,
            $provider->only([
                'vetting_status',
                'background_check_status',
                'trust_notes',
                'is_active',
                'last_reviewed_at',
                'last_reviewed_by_user_id',
                'trust_score',
                'reliability_score',
            ]),
        );

        return redirect()->route('admin.providers')
            ->with('success', 'Provider trust review saved.');
    }
}
