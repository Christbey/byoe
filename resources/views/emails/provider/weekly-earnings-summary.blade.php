@extends('emails.layouts.base')

@section('content')
    <h2 style="margin: 0 0 8px; font-size: 20px; font-weight: 600; color: #111827;">
        Your Week in Review
    </h2>

    <p style="margin: 0 0 24px; font-size: 16px; color: #6b7280;">
        Here's how you did this week, {{ $provider->user->name }}!
    </p>

    <table role="presentation" style="width: 100%; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 24px;">
        <tr style="background-color: #f9fafb;">
            <td style="padding: 32px; text-align: center;">
                <p style="margin: 0 0 8px; font-size: 36px; font-weight: 700; color: #059669;">
                    ${{ number_format($weeklyEarnings, 2) }}
                </p>
                <p style="margin: 0; font-size: 14px; color: #6b7280;">Total Earned</p>
            </td>
        </tr>
    </table>

    <table role="presentation" style="width: 100%; margin-bottom: 24px;">
        <tr>
            <td style="width: 50%; padding-right: 8px;">
                <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; text-align: center; background-color: #f9fafb;">
                    <p style="margin: 0 0 4px; font-size: 28px; font-weight: 700; color: #111827;">{{ $completedBookings }}</p>
                    <p style="margin: 0; font-size: 14px; color: #6b7280;">Shifts Completed</p>
                </div>
            </td>
            <td style="width: 50%; padding-left: 8px;">
                <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; text-align: center; background-color: #f9fafb;">
                    <p style="margin: 0 0 4px; font-size: 28px; font-weight: 700; color: #111827;">{{ number_format($averageRating, 1) }}★</p>
                    <p style="margin: 0; font-size: 14px; color: #6b7280;">Avg Rating</p>
                </div>
            </td>
        </tr>
    </table>

    <a href="{{ config('app.url') }}/provider/earnings" style="display: inline-block; padding: 12px 24px; background-color: #059669; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600;">
        View Detailed Earnings
    </a>
@endsection
