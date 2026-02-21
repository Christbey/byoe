@extends('emails.layouts.base')

@section('title', 'Upcoming Shift Reminder')

@section('content')
    <h2 style="margin: 0 0 8px; font-size: 20px; font-weight: 600; color: #111827;">
        Reminder: Shift Tomorrow
    </h2>

    <p style="margin: 0 0 24px; font-size: 16px; color: #374151; line-height: 1.5;">
        Your shift at <strong>{{ $booking->serviceRequest->shopLocation->shop->name }}</strong> is tomorrow!
    </p>

    <table role="presentation" style="width: 100%; border-collapse: collapse; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; margin-bottom: 24px;">
        <tr style="background-color: #f9fafb;">
            <td style="padding: 20px;">
                <p style="margin: 0 0 12px; font-size: 18px; font-weight: 600; color: #111827;">
                    {{ $booking->serviceRequest->title }}
                </p>

                <p style="margin: 0 0 8px; font-size: 14px; color: #374151;">
                    📍 {{ $booking->serviceRequest->shopLocation->address_line1 }}<br>
                    {{ $booking->serviceRequest->shopLocation->city }}, {{ $booking->serviceRequest->shopLocation->state }} {{ $booking->serviceRequest->shopLocation->zip_code }}
                </p>

                <p style="margin: 0 0 8px; font-size: 14px; color: #374151;">
                    📅 {{ $booking->serviceRequest->service_date->format('l, F j, Y') }}
                </p>

                <p style="margin: 0 0 16px; font-size: 14px; color: #374151;">
                    🕐 {{ \Carbon\Carbon::parse($booking->serviceRequest->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($booking->serviceRequest->end_time)->format('g:i A') }}
                </p>

                <p style="margin: 0; font-size: 18px; font-weight: 700; color: #059669;">
                    Pays: ${{ number_format($booking->serviceRequest->price, 2) }}
                </p>
            </td>
        </tr>
    </table>

    <a href="{{ config('app.url') }}/provider/bookings/{{ $booking->id }}"
       style="display: inline-block; padding: 12px 24px; background-color: #059669; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px;">
        View Booking Details
    </a>

    <p style="margin: 24px 0 0; font-size: 14px; color: #6b7280;">
        See you tomorrow! If you need to contact the shop, you can do so through the booking page.
    </p>
@endsection
