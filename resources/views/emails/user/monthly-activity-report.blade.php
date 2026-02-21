@extends('emails.layouts.base')

@section('content')
    <h2 style="margin: 0 0 8px; font-size: 20px; font-weight: 600; color: #111827;">
        Your {{ now()->format('F') }} Activity Report
    </h2>

    <p style="margin: 0 0 24px; font-size: 16px; color: #6b7280;">
        Here's a summary of your activity this month, {{ $user->name }}.
    </p>

    <table role="presentation" style="width: 100%; margin-bottom: 24px;">
        <tr>
            @foreach($stats as $label => $value)
                <td style="width: 33.33%; padding: 8px;">
                    <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; text-align: center; background-color: #f9fafb;">
                        <p style="margin: 0 0 4px; font-size: 24px; font-weight: 700; color: #111827;">{{ $value }}</p>
                        <p style="margin: 0; font-size: 12px; color: #6b7280;">{{ $label }}</p>
                    </div>
                </td>
            @endforeach
        </tr>
    </table>

    <a href="{{ config('app.url') }}/dashboard" style="display: inline-block; padding: 12px 24px; background-color: #059669; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600;">
        View Dashboard
    </a>
@endsection
