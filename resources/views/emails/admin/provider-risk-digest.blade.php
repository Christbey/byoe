@extends('emails.layouts.base')

@section('title', 'Provider Risk Digest')

@section('content')
    <h2 style="margin: 0 0 8px; font-size: 20px; font-weight: 600; color: #111827;">
        Provider risk digest
    </h2>

    <p style="margin: 0 0 24px; font-size: 16px; color: #374151;">
        {{ $pendingReviewCount }} provider{{ $pendingReviewCount === 1 ? '' : 's' }} waiting on trust review and
        {{ $openDisputeCount }} active dispute{{ $openDisputeCount === 1 ? '' : 's' }} currently need attention.
    </p>

    @foreach($providers as $provider)
        <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 12px; background-color: #f9fafb;">
            <p style="margin: 0 0 4px; font-weight: 600; color: #111827;">
                {{ $provider->user?->name ?? 'Provider #'.$provider->id }}
            </p>
            <p style="margin: 0 0 8px; font-size: 14px; color: #6b7280;">
                Trust {{ $provider->trust_score }} · Reliability {{ $provider->reliability_score }} ·
                Vetting {{ str_replace('_', ' ', $provider->vetting_status) }}
            </p>
            <p style="margin: 0; font-size: 14px; color: #374151;">
                {{ $provider->dispute_count }} disputes · {{ $provider->cancellation_rate }}% cancellations ·
                {{ $provider->no_show_count }} no-shows
            </p>
        </div>
    @endforeach

    <a href="{{ config('app.url') }}/admin/providers"
       style="display: inline-block; padding: 12px 24px; background-color: #111827; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; margin-top: 16px;">
        Review provider trust queue
    </a>
@endsection
