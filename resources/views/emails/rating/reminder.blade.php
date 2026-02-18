<x-mail::message>
# How Was Your Experience?

Hi {{ $user->name }},

We hope your recent service went well! We'd love to hear about your experience.

## Completed Service

**Date:** {{ $booking->serviceRequest->service_date->format('l, F j, Y') }}

@if($user->isShopOwner())
**Provider:** {{ $booking->provider->user->name }}

**Service:** {{ $booking->serviceRequest->title }}
@else
**Shop:** {{ $booking->serviceRequest->shopLocation->shop->name }}

**Location:** {{ $booking->serviceRequest->shopLocation->fullAddress() }}
@endif

---

Your feedback helps us maintain quality in our marketplace and helps others make informed decisions. It only takes a minute!

@if($user->isShopOwner())
<x-mail::button :url="route('shop.bookings.show', $booking)">
Leave a Rating
</x-mail::button>
@else
<x-mail::button :url="route('provider.bookings.show', $booking)">
Leave a Rating
</x-mail::button>
@endif

**Why ratings matter:**
- Help maintain high standards in our community
- Assist others in making informed hiring decisions
- Recognize excellence and professionalism
- Improve the overall marketplace experience

Thanks for being part of our community!

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

