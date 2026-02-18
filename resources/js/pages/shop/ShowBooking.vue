<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { Booking } from '@/types/marketplace';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';
import PageHelp from '@/components/marketplace/PageHelp.vue';
import PaymentForm from '@/components/marketplace/PaymentForm.vue';

interface Props {
    booking: Booking;
    clientSecret: string | null;
    stripePublishableKey: string;
    hasRated: boolean;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Bookings', href: '/shop/bookings' },
    { title: 'Booking Details', href: `/shop/bookings/${props.booking.id}` },
];

const isCompleting = ref(false);
const isCancelling = ref(false);
const ratingValue = ref(0);
const ratingComment = ref('');
const isRating = ref(false);

const formatCurrency = (amount: number) =>
    new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);

const formatDate = (date: string) =>
    new Intl.DateTimeFormat('en-US', { month: 'long', day: 'numeric', year: 'numeric' }).format(new Date(date));

const formatDateTime = (datetime: string) =>
    new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    }).format(new Date(datetime));

const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
        pending: 'secondary',
        confirmed: 'default',
        in_progress: 'default',
        completed: 'secondary',
        cancelled: 'destructive',
    };
    return colors[status] || 'outline';
};

const statusLabel = (status: string) => {
    const labels: Record<string, string> = {
        pending: 'Pending Payment',
        confirmed: 'Confirmed',
        in_progress: 'In Progress',
        completed: 'Completed',
        cancelled: 'Cancelled',
    };
    return labels[status] ?? status;
};

const handleComplete = () => {
    if (confirm('Mark this booking as complete?')) {
        isCompleting.value = true;
        router.post(
            `/shop/bookings/${props.booking.id}/complete`,
            {},
            { onFinish: () => { isCompleting.value = false; } },
        );
    }
};

const handleCancel = () => {
    if (confirm('Are you sure you want to cancel this booking? This action cannot be undone.')) {
        isCancelling.value = true;
        router.delete(`/shop/bookings/${props.booking.id}`, {
            onFinish: () => { isCancelling.value = false; },
        });
    }
};

const submitRating = () => {
    if (!ratingValue.value) return;
    isRating.value = true;
    router.post(
        `/shop/bookings/${props.booking.id}/rate`,
        { rating: ratingValue.value, comment: ratingComment.value },
        { onFinish: () => { isRating.value = false; } },
    );
};
</script>

<template>
    <Head title="Booking Details" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-2">
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold tracking-tight md:text-3xl">Booking Details</h1>
                        <Badge :variant="getStatusColor(booking.status)">
                            {{ statusLabel(booking.status) }}
                        </Badge>
                    </div>
                    <p class="text-sm text-muted-foreground md:text-base">
                        Created {{ formatDate(booking.created_at) }}
                    </p>
                    <PageHelp
                        storage-key="shop-show-booking"
                        :steps="[
                            'This booking was created when a provider accepted your service request.',
                            'Pay for the booking to confirm it — the provider will be notified.',
                            'Once the shift is done, click \'Mark Complete\' to finalize and release payment to the provider.',
                            'Rate your provider after completing the booking.',
                        ]"
                    />
                </div>
                <div class="flex gap-2">
                    <Button as="a" href="/shop/bookings" variant="outline">Back to List</Button>
                    <Button
                        v-if="booking.status === 'confirmed' || booking.status === 'in_progress'"
                        :disabled="isCompleting"
                        @click="handleComplete"
                    >
                        {{ isCompleting ? 'Completing...' : 'Mark Complete' }}
                    </Button>
                    <Button
                        v-if="booking.status === 'pending' || booking.status === 'confirmed'"
                        variant="destructive"
                        :disabled="isCancelling"
                        @click="handleCancel"
                    >
                        {{ isCancelling ? 'Cancelling...' : 'Cancel Booking' }}
                    </Button>
                </div>
            </div>

            <!-- Main Content -->
            <div class="grid gap-4 lg:grid-cols-3">
                <!-- Left Column -->
                <div class="space-y-4 lg:col-span-2">
                    <!-- Payment Required -->
                    <Card v-if="booking.status === 'pending' && clientSecret" class="border-amber-300 bg-amber-50 dark:bg-amber-950/20">
                        <CardHeader>
                            <CardTitle class="text-amber-800 dark:text-amber-300">Payment Required</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <p class="text-sm text-amber-700 dark:text-amber-400">
                                A provider has accepted your request. Complete payment to confirm the booking.
                            </p>
                            <PaymentForm
                                :client-secret="clientSecret"
                                :publishable-key="stripePublishableKey"
                                :amount="booking.service_price"
                                @success="router.reload()"
                            />
                        </CardContent>
                    </Card>

                    <!-- Provider not yet set up for payouts -->
                    <Card v-if="booking.status === 'pending' && !clientSecret" class="border-amber-300 bg-amber-50 dark:bg-amber-950/20">
                        <CardHeader>
                            <CardTitle class="text-amber-800 dark:text-amber-300">Awaiting Provider Setup</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-sm text-amber-700 dark:text-amber-400">
                                The provider needs to finish setting up their payout account before payment can be collected. We'll notify them — check back shortly or cancel this booking if it doesn't resolve.
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Service Request Details -->
                    <Card v-if="booking.service_request">
                        <CardHeader>
                            <CardTitle>Service Request</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Title</p>
                                <p class="text-lg font-semibold">{{ booking.service_request.title }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Description</p>
                                <p class="whitespace-pre-wrap text-sm">{{ booking.service_request.description }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Service Date & Time</p>
                                <p class="text-sm">
                                    {{ formatDate(booking.service_request.service_date) }}
                                    •
                                    {{ formatDateTime(booking.service_request.start_time) }}
                                    -
                                    {{ formatDateTime(booking.service_request.end_time) }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Provider Details -->
                    <Card v-if="booking.provider">
                        <CardHeader>
                            <CardTitle>Provider</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex items-start gap-4">
                                <div class="flex size-16 items-center justify-center rounded-full bg-primary/10">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-8 text-primary">
                                        <path d="M10 8a3 3 0 100-6 3 3 0 000 6zM3.465 14.493a1.23 1.23 0 00.41 1.412A9.957 9.957 0 0010 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 00-13.074.003z" />
                                    </svg>
                                </div>
                                <div class="flex-1 space-y-2">
                                    <div>
                                        <p class="text-sm font-medium text-muted-foreground">Experience</p>
                                        <p class="text-sm">{{ booking.provider.years_experience }} years</p>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-muted-foreground">Rating</p>
                                        <p class="text-sm">⭐ {{ Number(booking.provider.average_rating).toFixed(1) }}</p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Location Details -->
                    <Card v-if="booking.service_request?.shop_location">
                        <CardHeader>
                            <CardTitle>Location</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex items-start gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="mt-0.5 size-5 shrink-0 text-muted-foreground">
                                    <path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 103 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 002.273 1.765 11.842 11.842 0 00.976.544l.062.029.018.008.006.003zM10 11.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" clip-rule="evenodd" />
                                </svg>
                                <div class="text-sm">
                                    <p>{{ booking.service_request.shop_location.address_line_1 }}</p>
                                    <p v-if="booking.service_request.shop_location.address_line_2">
                                        {{ booking.service_request.shop_location.address_line_2 }}
                                    </p>
                                    <p>
                                        {{ booking.service_request.shop_location.city }},
                                        {{ booking.service_request.shop_location.state }}
                                        {{ booking.service_request.shop_location.zip_code }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Rate Provider -->
                    <Card v-if="booking.status === 'completed' && !hasRated">
                        <CardHeader>
                            <CardTitle>Rate Your Provider</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <p class="text-sm text-muted-foreground">How did the service go? Your rating helps other shops make informed decisions.</p>
                            <div class="flex gap-2">
                                <button
                                    v-for="star in 5"
                                    :key="star"
                                    class="text-2xl transition-transform hover:scale-110 focus:outline-none"
                                    :class="star <= ratingValue ? 'text-yellow-400' : 'text-muted-foreground/30'"
                                    @click="ratingValue = star"
                                >
                                    ★
                                </button>
                            </div>
                            <textarea
                                v-model="ratingComment"
                                rows="3"
                                placeholder="Leave a comment (optional)"
                                class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                            />
                            <Button :disabled="!ratingValue || isRating" @click="submitRating">
                                {{ isRating ? 'Submitting...' : 'Submit Rating' }}
                            </Button>
                        </CardContent>
                    </Card>

                    <Card v-if="booking.status === 'completed' && hasRated" class="border-green-200 bg-green-50 dark:bg-green-950/20">
                        <CardContent class="py-4">
                            <p class="text-sm text-green-700 dark:text-green-300">✓ You've rated this provider. Thank you!</p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Right Column - Summary -->
                <div class="space-y-4">
                    <!-- Pricing -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Pricing</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">You Pay</p>
                                <p class="text-2xl font-bold">{{ formatCurrency(booking.service_price) }}</p>
                            </div>
                            <div class="space-y-2 border-t pt-2 text-sm">
                                <div class="flex justify-between text-muted-foreground">
                                    <span>Platform fee</span>
                                    <span>{{ formatCurrency(booking.platform_fee) }}</span>
                                </div>
                                <div class="flex justify-between font-medium">
                                    <span>Provider receives</span>
                                    <span>{{ formatCurrency(booking.provider_payout) }}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Status -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Status</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Current Status</p>
                                <Badge :variant="getStatusColor(booking.status)" class="mt-1">
                                    {{ statusLabel(booking.status) }}
                                </Badge>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Created</p>
                                <p class="text-sm">{{ formatDateTime(booking.created_at) }}</p>
                            </div>
                            <div v-if="booking.updated_at !== booking.created_at">
                                <p class="text-sm font-medium text-muted-foreground">Last Updated</p>
                                <p class="text-sm">{{ formatDateTime(booking.updated_at) }}</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
