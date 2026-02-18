<x-mail::message>
# New Service Opportunity Available

Hello {{ $provider->user->name }},

Great news! A coffee shop in your area is looking for a barista with your skills.

## Service Details

**Shop:** {{ $serviceRequest->shopLocation->shop->name }}

**Date:** {{ $serviceRequest->service_date->format('l, F j, Y') }}

**Time:** {{ $serviceRequest->start_time->format('g:i A') }} - {{ $serviceRequest->end_time->format('g:i A') }}

**Location:** {{ $serviceRequest->shopLocation->fullAddress() }}

**Pay:** ${{ number_format($serviceRequest->price, 2) }}

## Skills Required

@if($serviceRequest->skills_required)
@foreach($serviceRequest->skills_required as $skill)
- {{ ucfirst($skill) }}
@endforeach
@else
- No specific skills required
@endif

## About This Request

{{ $serviceRequest->description }}

---

This is a great opportunity to showcase your skills and expand your network in the coffee community. Don't let it slip away!

<x-mail::button :url="route('provider.available-requests')">
View Request
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

