<x-mail::message>
# Payout Scheduled

Great news! Your payout has been scheduled and will be deposited to your account soon.

## Payout Details

**Amount:** {{ $payout->formattedAmount() }}

**Booking Reference:** #{{ $payout->booking_id }}

**Service:** {{ $payout->booking->serviceRequest->title }}

**Shop:** {{ $payout->booking->serviceRequest->shopLocation->shop->name }}

**Expected Deposit Date:** {{ $payout->scheduled_for->format('l, F j, Y') }}

@if($payout->provider->stripeAccount && $payout->provider->stripeAccount->payouts_enabled)
**Bank Account:** ****{{ substr($payout->provider->stripeAccount->stripe_account_id ?? '', -4) }}
@endif

---

Your payout will be automatically transferred to your connected bank account. Depending on your bank, it may take 1-2 business days for the funds to appear in your account.

<x-mail::button :url="route('provider.payouts')">
View Payout Details
</x-mail::button>

If you have any questions about this payout, please contact our support team.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

