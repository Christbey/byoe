@extends('emails.layouts.base')

@section('title', 'Mark Booking Complete')

@section('content')
    <h2 style="margin: 0 0 8px; font-size: 20px; font-weight: 600; color: #111827;">
        Time to Complete Your Booking
    </h2>

    <p style="margin: 0 0 24px; font-size: 16px; color: #374151; line-height: 1.5;">
        Your shift with <strong>{{ $booking->provider->user->name }}</strong> was yesterday. Please mark it complete and rate your contractor.
    </p>

    <table role="presentation" style="width: 100%; border-collapse: collapse; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; margin-bottom: 24px;">
        <tr style="background-color: #f9fafb;">
            <td style="padding: 20px;">
                <p style="margin: 0 0 12px; font-size: 18px; font-weight: 600; color: #111827;">
                    {{ $booking->serviceRequest->title }}
                </p>

                <p style="margin: 0 0 8px; font-size: 14px; color: #374151;">
                    👤 {{ $booking->provider->user->name }}
                </p>

                <p style="margin: 0 0 8px; font-size: 14px; color: #374151;">
                    📅 {{ $booking->serviceRequest->service_date->format('l, F j, Y') }}
                </p>

                <p style="margin: 0; font-size: 18px; font-weight: 700; color: #059669;">
                    ${{ number_format($booking->serviceRequest->price, 2) }}
                </p>
            </td>
        </tr>
    </table>

    <div style="background-color: #dbeafe; border-left: 4px solid #3b82f6; padding: 16px; margin-bottom: 24px; border-radius: 4px;">
        <p style="margin: 0; font-size: 14px; color: #1e40af;">
            ℹ️ Payment will be released to the contractor once you mark the booking complete.
        </p>
    </div>

    <a href="{{ config('app.url') }}/shop/bookings/{{ $booking->id }}"
       style="display: inline-block; padding: 12px 24px; background-color: #059669; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px;">
        Mark Complete & Rate
    </a>
@endsection
