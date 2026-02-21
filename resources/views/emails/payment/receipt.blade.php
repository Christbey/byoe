<x-mail::message>
# Payment Receipt

Thank you for your payment. Here is your receipt for this transaction.

---

## Payment Information

**Receipt Number:** {{ $payment->id }}

**Payment Date:** {{ $payment->paid_at->format('F j, Y \a\t g:i A') }}

**Payment Method:** {{ ucfirst($payment->payment_method_type) }} ending in {{ $payment->last_four }}

**Payment Status:** {{ ucfirst($payment->status) }}

---

## Service Details

**Booking Reference:** #{{ $payment->booking_id }}

**Service:** {{ $payment->booking->serviceRequest->title }}

**Provider:** {{ $payment->booking->provider->user->name }}

**Shop:** {{ $payment->booking->serviceRequest->shopLocation->shop->name }}

**Service Date:** {{ $payment->booking->serviceRequest->service_date->format('l, F j, Y') }}

**Service Time:** {{ \Carbon\Carbon::createFromTimeString($payment->booking->serviceRequest->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::createFromTimeString($payment->booking->serviceRequest->end_time)->format('g:i A') }}

---

<x-mail::table>
| Description | Amount |
|:----------- | ------:|
| Service Fee | ${{ number_format($payment->booking->service_price, 2) }} |
| Platform Fee | ${{ number_format($payment->booking->platform_fee, 2) }} |
| **Total Paid** | **${{ number_format($payment->amount, 2) }}** |
</x-mail::table>

---

<x-mail::button :url="route('shop.payments.receipt', $payment)">
View Receipt Details
</x-mail::button>

This email serves as your receipt. Please keep it for your records.

If you have any questions about this charge, please contact our support team.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

