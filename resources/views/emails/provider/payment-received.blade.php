@extends('emails.layouts.base')

@section('content')
    <h2 style="margin: 0 0 16px; font-size: 20px; font-weight: 600; color: #059669; text-align: center;">
        Payment Received! 💰
    </h2>

    <p style="margin: 0 0 24px; font-size: 16px; color: #374151; text-align: center;">
        Your payment of <strong>${{ number_format($payout->amount, 2) }}</strong> has been processed!
    </p>

    <table role="presentation" style="width: 100%; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 24px;">
        <tr style="background-color: #f9fafb;">
            <td style="padding: 20px;">
                <p style="margin: 0 0 8px;"><strong>Amount:</strong> ${{ number_format($payout->amount, 2) }}</p>
                <p style="margin: 0 0 8px;"><strong>Booking:</strong> {{ $payout->booking->serviceRequest->title }}</p>
                <p style="margin: 0;"><strong>Processed:</strong> {{ $payout->processed_at->format('M j, Y g:i A') }}</p>
            </td>
        </tr>
    </table>

    <p style="margin: 0; font-size: 14px; color: #6b7280; text-align: center;">
        Funds should appear in your bank account within 1-2 business days.
    </p>
@endsection
