@extends('emails.layouts.base')

@section('content')
    <h2 style="margin: 0 0 16px; font-size: 20px; font-weight: 600; color: #111827; text-align: center;">
        You Received a New Rating!
    </h2>

    <p style="margin: 0 0 24px; text-align: center;">
        @for($i = 1; $i <= 5; $i++)
            <span style="font-size: 32px; color: {{ $i <= $rating->rating ? '#f59e0b' : '#e5e7eb' }};">★</span>
        @endfor
    </p>

    <table role="presentation" style="width: 100%; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 24px;">
        <tr style="background-color: #f9fafb;">
            <td style="padding: 20px;">
                <p style="margin: 0 0 8px;"><strong>From:</strong> {{ $rating->rater->shop->name ?? $rating->rater->name }}</p>
                <p style="margin: 0 0 8px;"><strong>Booking:</strong> {{ $rating->booking->serviceRequest->title }}</p>
                @if($rating->comment)
                    <p style="margin: 16px 0 0; padding-top: 16px; border-top: 1px solid #e5e7eb; color: #374151; font-style: italic;">
                        "{{ $rating->comment }}"
                    </p>
                @endif
            </td>
        </tr>
    </table>

    <a href="{{ config('app.url') }}/provider/profile" style="display: inline-block; padding: 12px 24px; background-color: #059669; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600;">
        View Your Profile
    </a>
@endsection
