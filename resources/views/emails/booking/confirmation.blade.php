<x-mail::message>
# Booking Confirmed

Your booking has been confirmed! Here are the details:

## Confirmation Details

**Confirmation Number:** #{{ $booking->id }}

**Service Date:** {{ $booking->serviceRequest->service_date->format('l, F j, Y') }}

**Time:** {{ $booking->serviceRequest->start_time->format('g:i A') }} - {{ $booking->serviceRequest->end_time->format('g:i A') }}

**Location:** {{ $booking->serviceRequest->shopLocation->fullAddress() }}

@if($recipient->isShopOwner())
**Provider:** {{ $booking->provider->user->name }}
@else
**Shop:** {{ $booking->serviceRequest->shopLocation->shop->name }}
@endif

**Service Price:** ${{ number_format($booking->service_price, 2) }}

## Next Steps

@if($recipient->isShopOwner())
- Your provider will arrive at the scheduled time
- Make sure your location is accessible
- Have any necessary equipment or materials ready
- Payment will be processed automatically
@else
- Review the service details above
- Arrive 10-15 minutes early if possible
- Contact the shop if you need directions
- Your payout will be processed after service completion
@endif

@if($recipient->isShopOwner())
<x-mail::button :url="route('shop.bookings.show', $booking)">
View Booking
</x-mail::button>
@else
<x-mail::button :url="route('provider.bookings.show', $booking)">
View Booking
</x-mail::button>
@endif

If you have any questions, please don't hesitate to reach out.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

