<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\StoreCertificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CertificationsController extends Controller
{
    public function show(Request $request): Response
    {
        $provider = $request->user()->provider;

        return Inertia::render('provider/Certifications', [
            'certifications' => $provider?->certifications ?? [],
        ]);
    }

    public function store(StoreCertificationRequest $request): RedirectResponse
    {
        $provider = $request->user()->provider;

        $certifications = $provider->certifications ?? [];
        $certifications[] = array_merge(
            $request->validated(),
            ['id' => uniqid(), 'added_at' => now()->toDateString()]
        );

        $provider->update(['certifications' => $certifications]);

        return redirect()->back()->with('success', 'Certification added successfully.');
    }

    public function destroy(Request $request, string $certId): RedirectResponse
    {
        $provider = $request->user()->provider;

        $certifications = collect($provider->certifications ?? [])
            ->reject(fn ($cert) => $cert['id'] === $certId)
            ->values()
            ->toArray();

        $provider->update(['certifications' => $certifications]);

        return redirect()->back()->with('success', 'Certification removed.');
    }
}
