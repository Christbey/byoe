@extends('emails.layouts.base')

@section('content')
    <h2 style="margin: 0 0 8px; font-size: 20px; font-weight: 600; color: #111827;">
        Complete Your Profile
    </h2>

    <p style="margin: 0 0 24px; font-size: 16px; color: #374151;">
        Hi {{ $user->name }}, you're almost ready to get started! Complete your profile to unlock all features.
    </p>

    <div style="background-color: #dbeafe; border-left: 4px solid #3b82f6; padding: 16px; margin-bottom: 24px; border-radius: 4px;">
        <p style="margin: 0; font-size: 14px; color: #1e40af;">
            💡 Complete profiles get {{ $userType === 'provider' ? '3x more bookings' : '50% faster responses' }}!
        </p>
    </div>

    @if($userType === 'provider')
        <a href="{{ config('app.url') }}/provider/onboarding" style="display: inline-block; padding: 12px 24px; background-color: #059669; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600;">
            Complete Provider Profile
        </a>
    @else
        <a href="{{ config('app.url') }}/shop/onboarding" style="display: inline-block; padding: 12px 24px; background-color: #059669; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600;">
            Complete Shop Profile
        </a>
    @endif
@endsection
