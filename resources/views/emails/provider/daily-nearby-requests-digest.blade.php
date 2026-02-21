@extends('emails.layouts.base')

@section('title', 'Daily Nearby Shifts')

@section('content')
    <h2 style="margin: 0 0 16px; font-size: 20px; font-weight: 600; color: #111827;">
        Good morning, {{ $provider->user->name }}!
    </h2>

    @if($requests->count() > 0)
        <p style="margin: 0 0 24px; font-size: 16px; color: #374151; line-height: 1.5;">
            We found <strong>{{ $requests->count() }}</strong> {{ str('shift')->plural($requests->count()) }} near you that match your skills:
        </p>

        @foreach($requests as $request)
            <table role="presentation" style="width: 100%; border-collapse: collapse; margin-bottom: 16px; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
                <tr style="background-color: #f9fafb;">
                    <td style="padding: 16px;">
                        <h3 style="margin: 0 0 8px; font-size: 18px; font-weight: 600; color: #111827;">
                            {{ $request->title }}
                        </h3>
                        <p style="margin: 0 0 12px; font-size: 14px; color: #6b7280;">
                            {{ $request->shopLocation->shop->name }} · {{ $request->shopLocation->city }}, {{ $request->shopLocation->state }}
                        </p>
                        <div style="display: flex; gap: 16px; margin-bottom: 12px;">
                            <span style="font-size: 14px; color: #374151;">
                                📅 {{ $request->service_date->format('M j, Y') }}
                            </span>
                            <span style="font-size: 14px; color: #374151;">
                                🕐 {{ \Carbon\Carbon::parse($request->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($request->end_time)->format('g:i A') }}
                            </span>
                        </div>
                        <p style="margin: 0 0 16px; font-size: 20px; font-weight: 700; color: #059669;">
                            ${{ number_format($request->price, 2) }}
                        </p>
                        <a href="{{ config('app.url') }}/provider/available-requests?highlight={{ $request->id }}"
                           style="display: inline-block; padding: 10px 20px; background-color: #059669; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px;">
                            View & Accept
                        </a>
                    </td>
                </tr>
            </table>
        @endforeach

        <p style="margin: 24px 0 0; font-size: 14px; color: #6b7280; text-align: center;">
            <a href="{{ config('app.url') }}/provider/available-requests" style="color: #059669; text-decoration: underline;">
                View all {{ $requests->count() }} {{ str('shift')->plural($requests->count()) }} →
            </a>
        </p>
    @else
        <p style="margin: 0 0 16px; font-size: 16px; color: #374151; line-height: 1.5;">
            No new shifts available in your area today.
        </p>
        <p style="margin: 0; font-size: 14px; color: #6b7280;">
            Check back later or <a href="{{ config('app.url') }}/provider/available-requests" style="color: #059669; text-decoration: underline;">browse all available shifts</a>.
        </p>
    @endif
@endsection
