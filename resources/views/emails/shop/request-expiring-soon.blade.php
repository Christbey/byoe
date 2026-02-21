@extends('emails.layouts.base')

@section('title', 'Request Expiring Soon')

@section('content')
    <h2 style="margin: 0 0 8px; font-size: 20px; font-weight: 600; color: #111827;">
        Your Shift Request Needs Attention
    </h2>

    <p style="margin: 0 0 24px; font-size: 16px; color: #374151; line-height: 1.5;">
        No contractors have accepted your shift request yet, and it expires in <strong>{{ $request->expires_at->diffForHumans() }}</strong>.
    </p>

    <table role="presentation" style="width: 100%; border-collapse: collapse; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; margin-bottom: 24px;">
        <tr style="background-color: #f9fafb;">
            <td style="padding: 20px;">
                <p style="margin: 0 0 12px; font-size: 18px; font-weight: 600; color: #111827;">
                    {{ $request->title }}
                </p>

                <p style="margin: 0 0 8px; font-size: 14px; color: #374151;">
                    📅 {{ $request->service_date->format('l, F j, Y') }}
                </p>

                <p style="margin: 0 0 8px; font-size: 14px; color: #374151;">
                    🕐 {{ \Carbon\Carbon::parse($request->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($request->end_time)->format('g:i A') }}
                </p>

                <p style="margin: 0; font-size: 18px; font-weight: 700; color: #059669;">
                    ${{ number_format($request->price, 2) }}
                </p>
            </td>
        </tr>
    </table>

    <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 16px; margin-bottom: 24px; border-radius: 4px;">
        <p style="margin: 0 0 12px; font-size: 14px; color: #92400e; font-weight: 600;">
            💡 Tips to get your shift filled:
        </p>
        <ul style="margin: 0; padding-left: 20px; color: #92400e; font-size: 14px;">
            <li>Increase the pay rate to attract more contractors</li>
            <li>Extend the expiration date to give more time</li>
            <li>Review your skill requirements - are they too specific?</li>
        </ul>
    </div>

    <a href="{{ config('app.url') }}/shop/service-requests/{{ $request->id }}"
       style="display: inline-block; padding: 12px 24px; background-color: #059669; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px; margin-right: 12px;">
        View Request
    </a>

    <a href="{{ config('app.url') }}/shop/service-requests/{{ $request->id }}/edit"
       style="display: inline-block; padding: 12px 24px; background-color: #6b7280; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px;">
        Edit Request
    </a>
@endsection
