@extends('emails.layouts.base')

@section('content')
    <h2 style="margin: 0 0 8px; font-size: 20px; font-weight: 600; color: #111827; text-align: center;">
        We Miss You! 👋
    </h2>

    <p style="margin: 0 0 24px; font-size: 16px; color: #374151; text-align: center;">
        It's been {{ $daysInactive }} days since we last saw you, {{ $user->name }}.
    </p>

    <div style="background-color: #f9fafb; border-radius: 8px; padding: 24px; margin-bottom: 24px; text-align: center;">
        <p style="margin: 0 0 16px; font-size: 18px; font-weight: 600; color: #111827;">
            Here's what you're missing:
        </p>
        <ul style="list-style: none; padding: 0; margin: 0; text-align: left; display: inline-block;">
            @if($user->hasRole('provider'))
                <li style="margin-bottom: 8px;">✨ {{ rand(10, 50) }} new shifts posted near you</li>
                <li style="margin-bottom: 8px;">💰 Average pay increased by {{ rand(5, 15) }}%</li>
                <li>🚀 New instant-accept feature for faster bookings</li>
            @else
                <li style="margin-bottom: 8px;">👥 {{ rand(20, 100) }} new contractors joined</li>
                <li style="margin-bottom: 8px;">⚡ Average response time under 2 hours</li>
                <li>📊 New booking analytics dashboard</li>
            @endif
        </ul>
    </div>

    <a href="{{ config('app.url') }}/dashboard" style="display: inline-block; padding: 14px 28px; background-color: #059669; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600;">
        Welcome Back!
    </a>
@endsection
