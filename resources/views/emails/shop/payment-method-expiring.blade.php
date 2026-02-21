@extends('emails.layouts.base')

@section('content')
    <h2 style="margin: 0 0 8px; font-size: 20px; font-weight: 600; color: #111827;">
        Payment Method Expiring Soon
    </h2>

    <p style="margin: 0 0 24px; font-size: 16px; color: #374151;">
        Your payment method ending in <strong>{{ $lastFour }}</strong> expires on <strong>{{ $expiryDate }}</strong>.
    </p>

    <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 16px; margin-bottom: 24px; border-radius: 4px;">
        <p style="margin: 0; font-size: 14px; color: #92400e;">
            ⚠️ Update your payment method to avoid service interruptions.
        </p>
    </div>

    <a href="{{ config('app.url') }}/shop/payments?tab=method" style="display: inline-block; padding: 12px 24px; background-color: #059669; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600;">
        Update Payment Method
    </a>
@endsection
