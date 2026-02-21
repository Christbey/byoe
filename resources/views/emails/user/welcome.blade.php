@extends('emails.layouts.base')

@section('title', 'Welcome!')

@section('content')
    <h2 style="margin: 0 0 16px; font-size: 24px; font-weight: 700; color: #111827; text-align: center;">
        Welcome to ShiftFinder! 🎉
    </h2>

    <p style="margin: 0 0 24px; font-size: 16px; color: #374151; line-height: 1.6; text-align: center;">
        Hi {{ $user->name }}, we're excited to have you on board!
    </p>

    @if($user->hasRole('provider'))
        <p style="margin: 0 0 16px; font-size: 16px; color: #374151; line-height: 1.6;">
            As a contractor, you can now:
        </p>
        <ul style="margin: 0 0 24px; padding-left: 24px; color: #374151; line-height: 1.8;">
            <li>Browse available shifts near you</li>
            <li>Accept jobs that fit your schedule</li>
            <li>Get paid directly via Stripe</li>
        </ul>
        <a href="{{ config('app.url') }}/provider/available-requests"
           style="display: inline-block; padding: 14px 28px; background-color: #059669; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px;">
            Browse Available Shifts
        </a>
    @else
        <p style="margin: 0 0 16px; font-size: 16px; color: #374151; line-height: 1.6;">
            As a shop owner, you can now:
        </p>
        <ul style="margin: 0 0 24px; padding-left: 24px; color: #374151; line-height: 1.8;">
            <li>Post service requests for shifts</li>
            <li>Get matched with skilled contractors</li>
            <li>Manage bookings and payments easily</li>
        </ul>
        <a href="{{ config('app.url') }}/shop/service-requests/create"
           style="display: inline-block; padding: 14px 28px; background-color: #059669; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px;">
            Post Your First Shift
        </a>
    @endif

    <p style="margin: 32px 0 0; font-size: 14px; color: #6b7280; text-align: center;">
        Need help? <a href="{{ config('app.url') }}/dashboard" style="color: #059669; text-decoration: underline;">Visit your dashboard</a> to get started.
    </p>
@endsection
