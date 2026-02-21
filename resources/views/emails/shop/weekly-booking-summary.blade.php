@extends('emails.layouts.base')

@section('content')
    <h2 style="margin: 0 0 8px; font-size: 20px; font-weight: 600; color: #111827;">
        Your Weekly Booking Summary
    </h2>

    <p style="margin: 0 0 24px; font-size: 16px; color: #6b7280;">
        Here's what's happening with your shifts this week.
    </p>

    @if($upcomingBookings->count() > 0)
        <h3 style="margin: 0 0 12px; font-size: 16px; font-weight: 600;">Upcoming Shifts ({{ $upcomingBookings->count() }})</h3>
        @foreach($upcomingBookings->take(5) as $booking)
            <div style="border: 1px solid #e5e7eb; border-radius: 6px; padding: 16px; margin-bottom: 12px; background-color: #f9fafb;">
                <p style="margin: 0 0 4px; font-weight: 600;">{{ $booking->serviceRequest->title }}</p>
                <p style="margin: 0; font-size: 14px; color: #6b7280;">
                    {{ $booking->provider->user->name }} · {{ $booking->serviceRequest->service_date->format('M j') }}
                </p>
            </div>
        @endforeach
    @endif

    @if($completedBookings->count() > 0)
        <h3 style="margin: 24px 0 12px; font-size: 16px; font-weight: 600;">Completed This Week ({{ $completedBookings->count() }})</h3>
        <p style="margin: 0 0 16px; font-size: 14px; color: #6b7280;">
            Total spent: <strong>${{ number_format($totalSpent, 2) }}</strong>
        </p>
    @endif

    <a href="{{ config('app.url') }}/shop/bookings" style="display: inline-block; padding: 12px 24px; background-color: #059669; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; margin-top: 16px;">
        View All Bookings
    </a>
@endsection
