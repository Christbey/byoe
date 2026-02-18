<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
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
            'needsProfile' => ! $provider,
            'certifications' => $provider?->certifications ?? [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $provider = $request->user()->provider;

        if (! $provider) {
            return redirect()->back()->with('error', 'Provider profile not found.');
        }

        $validated = $request->validate([
            'type' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'issuer' => ['nullable', 'string', 'max:255'],
        ]);

        $certifications = $provider->certifications ?? [];
        $certifications[] = array_merge($validated, ['id' => uniqid(), 'added_at' => now()->toDateString()]);

        $provider->update(['certifications' => $certifications]);

        return redirect()->back()->with('success', 'Certification added successfully.');
    }

    public function destroy(Request $request, string $certId): RedirectResponse
    {
        $provider = $request->user()->provider;

        if (! $provider) {
            return redirect()->back()->with('error', 'Provider profile not found.');
        }

        $certifications = collect($provider->certifications ?? [])
            ->reject(fn ($cert) => $cert['id'] === $certId)
            ->values()
            ->toArray();

        $provider->update(['certifications' => $certifications]);

        return redirect()->back()->with('success', 'Certification removed.');
    }
}
