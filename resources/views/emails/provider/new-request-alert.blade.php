@extends('emails.layouts.base')

@section('content')
    <h2 style="margin: 0 0 8px; font-size: 20px; font-weight: 600; color: #111827;">
        New Shift Available!
    </h2>

    <p style="margin: 0 0 24px; font-size: 16px; color: #374151;">
        A new shift matching your skills just posted:
    </p>

    <table role="presentation" style="width: 100%; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 24px;">
        <tr style="background-color: #f9fafb;">
            <td style="padding: 20px;">
                <h3 style="margin: 0 0 12px; font-size: 18px; font-weight: 600;">{{ $request->title }}</h3>
                <p style="margin: 0 0 8px; color: #6b7280;">{{ $request->shopLocation->shop->name }}</p>
                <p style="margin: 0 0 12px; color: #374151;">
                    📅 {{ $request->service_date->format('M j, Y') }} at {{ \Carbon\Carbon::parse($request->start_time)->format('g:i A') }}
                </p>
                <p style="margin: 0; font-size: 20px; font-weight: 700; color: #059669;">${{ number_format($request->price, 2) }}</p>
            </td>
        </tr>
    </table>

    <a href="{{ config('app.url') }}/provider/available-requests?highlight={{ $request->id }}"
       style="display: inline-block; padding: 12px 24px; background-color: #059669; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600;">
        View & Accept
    </a>
@endsection
