<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { Booking } from '@/types/marketplace';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';
import { ref } from 'vue';

interface Props {
    booking: Booking;
    hasRated: boolean;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'My Bookings', href: '/provider/bookings' },
    { title: 'Booking Details', href: `/provider/bookings/${props.booking.id}` },
];

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

const completing = ref(false);
const ratingValue = ref(0);
const ratingComment = ref('');
const isRating = ref(false);

const completeBooking = () => {
    if (!confirm('Mark this booking as complete? This confirms the service has been delivered.')) return;
    completing.value = true;
    router.post(
        `/provider/bookings/${props.booking.id}/complete`,
        {},
        { onFinish: () => { completing.value = false; } },
    );
};

const submitRating = () => {
    if (!ratingValue.value) return;
    isRating.value = true;
    router.post(
        `/provider/bookings/${props.booking.id}/rate`,
        { rating: ratingValue.value, comment: ratingComment.value },
        { onFinish: () => { isRating.value = false; } },
    );
};

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

const shopName = props.booking.service_request?.shop_location?.shop?.name ?? 'Shop';
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
                        Accepted {{ formatDate(booking.created_at) }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <!-- Only allow completing a confirmed booking (payment received) -->
                    <Button
                        v-if="['confirmed', 'in_progress'].includes(booking.status)"
                        :disabled="completing"
                        variant="default"
                        @click="completeBooking"
                    >
                        {{ completing ? 'Completing...' : 'Mark Complete' }}
                    </Button>
                    <Button as="a" href="/provider/bookings" variant="outline">Back to List</Button>
                </div>
            </div>

            <!-- Pending notice — waiting on shop to pay -->
            <div
                v-if="booking.status === 'pending'"
                class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-800 dark:bg-amber-950/20 dark:text-amber-300"
            >
                Waiting for the shop to complete payment before this booking is confirmed.
            </div>

            <!-- Main Content -->
            <div class="grid gap-4 lg:grid-cols-3">
                <!-- Left Column - Details -->
                <div class="space-y-4 lg:col-span-2">
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

                    <!-- Shop Details -->
                    <Card v-if="booking.service_request?.shop_location?.shop">
                        <CardHeader>
                            <CardTitle>{{ shopName }}</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex items-start gap-4">
                                <div class="flex size-16 items-center justify-center rounded-full bg-primary/10">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-8 text-primary">
                                        <path fill-rule="evenodd" d="M1 2.75A.75.75 0 011.75 2h16.5a.75.75 0 010 1.5H18v8.75A2.75 2.75 0 0115.25 15h-1.072l.798 3.06a.75.75 0 01-1.452.38L13.41 18H6.59l-.114.44a.75.75 0 01-1.452-.38L5.823 15H4.75A2.75 2.75 0 012 12.25V3.5h-.25A.75.75 0 011 2.75zM7.373 15l-.391 1.5h6.037l-.392-1.5H7.373zM13.25 5a.75.75 0 01.75.75v5.5a.75.75 0 01-1.5 0v-5.5a.75.75 0 01.75-.75zm-6.5 0a.75.75 0 01.75.75v5.5a.75.75 0 01-1.5 0v-5.5A.75.75 0 016.75 5z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="flex-1 space-y-2">
                                    <div>
                                        <p class="text-lg font-semibold">{{ booking.service_request.shop_location.shop.name }}</p>
                                    </div>
                                    <div v-if="booking.service_request.shop_location.shop.phone">
                                        <p class="text-sm font-medium text-muted-foreground">Phone</p>
                                        <p class="text-sm">{{ booking.service_request.shop_location.shop.phone }}</p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Location Details -->
                    <Card v-if="booking.service_request?.shop_location">
                        <CardHeader>
                            <CardTitle>Service Location</CardTitle>
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

                    <!-- Rate the Shop -->
                    <Card v-if="booking.status === 'completed' && !hasRated">
                        <CardHeader>
                            <CardTitle>Rate {{ shopName }}</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <p class="text-sm text-muted-foreground">How was your experience working with this shop?</p>
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
                            <p class="text-sm text-green-700 dark:text-green-300">✓ You've rated this shop. Thank you!</p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Right Column - Summary -->
                <div class="space-y-4">
                    <!-- Your Payout -->
                    <Card class="border-primary/50">
                        <CardHeader>
                            <CardTitle>Your Payout</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Service Price</p>
                                <p class="text-lg font-semibold">{{ formatCurrency(booking.service_price) }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Platform Fee</p>
                                <p class="text-sm text-muted-foreground">- {{ formatCurrency(booking.platform_fee) }}</p>
                            </div>
                            <div class="border-t border-primary/30 pt-2">
                                <p class="text-sm font-medium text-muted-foreground">You'll Receive</p>
                                <p class="text-2xl font-bold text-primary">{{ formatCurrency(booking.provider_payout) }}</p>
                            </div>
                            <div v-if="booking.status === 'completed'" class="border-t pt-2">
                                <p class="text-xs text-muted-foreground">Payout will be processed within 3-5 business days</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Status Timeline -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Booking Status</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Current Status</p>
                                <Badge :variant="getStatusColor(booking.status)" class="mt-1">
                                    {{ statusLabel(booking.status) }}
                                </Badge>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Accepted On</p>
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
