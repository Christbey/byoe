<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { Booking, PaginatedResponse } from '@/types/marketplace';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';
import PageHelp from '@/components/marketplace/PageHelp.vue';

interface Props {
    bookings: PaginatedResponse<Booking>;
    filter?: string;
}

const props = withDefaults(defineProps<Props>(), { filter: 'all' });

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Bookings', href: '/shop/bookings' }];

const activeFilter = ref(props.filter);
const completingBookingId = ref<number | null>(null);
const ratingBookingId = ref<number | null>(null);
const rating = ref(0);

const filters = [
    { key: 'all', label: 'All' },
    { key: 'active', label: 'Active' },
    { key: 'completed', label: 'Completed' },
    { key: 'cancelled', label: 'Cancelled' },
];

const handleFilterChange = (key: string) => {
    activeFilter.value = key;
    router.get('/shop/bookings', { filter: key }, { preserveState: true, preserveScroll: true });
};

const handleCompleteBooking = (bookingId: number) => {
    if (confirm('Mark this booking as complete?')) {
        completingBookingId.value = bookingId;
        router.post(`/shop/bookings/${bookingId}/complete`, {}, {
            preserveScroll: true,
            onFinish: () => {
                completingBookingId.value = null;
                ratingBookingId.value = bookingId;
            },
        });
    }
};

const handleSubmitRating = (bookingId: number) => {
    if (rating.value === 0) {
        alert('Please select a rating');
        return;
    }
    router.post(`/shop/bookings/${bookingId}/rate`, { rating: rating.value }, {
        preserveScroll: true,
        onSuccess: () => {
            ratingBookingId.value = null;
            rating.value = 0;
        },
    });
};

const formatCurrency = (amount: number) =>
    new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);

const formatDate = (date: string) =>
    date ? new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(date)) : '—';

const formatTime = (time: string) =>
    time ? new Date(time).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' }) : '';


const stepLabels = ['Pending', 'Confirmed', 'In Progress', 'Complete'];

const statusStep = (status: string) =>
    ({ pending: 1, confirmed: 2, in_progress: 3, completed: 4, cancelled: 0 }[status] ?? 0);
</script>

<template>
    <Head title="Bookings" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
            <!-- Header -->
            <div class="space-y-2">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">Bookings</h1>
                <p class="text-sm text-muted-foreground md:text-base">Track your bookings with providers</p>
                <PageHelp storage-key="shop-bookings" :steps="['Bookings are created automatically when a provider accepts one of your service requests.', 'A booking starts as \'Pending\' until payment is processed, then becomes \'Confirmed\'.', 'After the shift is complete, mark the booking as complete and rate the provider.', 'Use the filter tabs to view active, completed, or cancelled bookings.']" />
            </div>

            <!-- Filter Tabs -->
            <div class="flex gap-2 overflow-x-auto pb-2 -mx-4 px-4 md:mx-0 md:px-0">
                <Badge
                    v-for="f in filters"
                    :key="f.key"
                    :variant="activeFilter === f.key ? 'default' : 'outline'"
                    as="button"
                    @click="handleFilterChange(f.key)"
                    class="cursor-pointer whitespace-nowrap px-4 py-2 text-sm font-medium transition-colors touch-manipulation"
                >
                    {{ f.label }}
                </Badge>
            </div>

            <!-- Count -->
            <div class="text-sm text-muted-foreground">
                Showing {{ bookings.data.length }} of {{ bookings.total }} bookings
            </div>

            <!-- Bookings List -->
            <div v-if="bookings.data.length > 0" class="space-y-4">
                <Card v-for="booking in bookings.data" :key="booking.id">
                    <CardContent class="pt-6">
                        <div class="flex flex-col gap-4">
                            <!-- Header row -->
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1 min-w-0 space-y-1">
                                    <h3 class="font-semibold text-lg">
                                        {{ booking.service_request?.title || 'Service Booking' }}
                                    </h3>
                                    <p class="text-sm text-muted-foreground">
                                        {{ formatDate(booking.service_request?.service_date || '') }}
                                        <template v-if="booking.service_request?.start_time">
                                            · {{ formatTime(booking.service_request.start_time) }}
                                            – {{ formatTime(booking.service_request.end_time || '') }}
                                        </template>
                                    </p>
                                </div>
                                <Badge :variant="booking.status_variant">
                                    {{ booking.status_label }}
                                </Badge>
                            </div>

                            <!-- Provider info -->
                            <div
                                v-if="booking.provider"
                                class="flex items-center gap-3 p-3 bg-muted/50 rounded-lg"
                            >
                                <div class="size-9 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5 text-primary">
                                        <path d="M10 8a3 3 0 100-6 3 3 0 000 6zM3.465 14.493a1.23 1.23 0 00.41 1.412A9.957 9.957 0 0010 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 00-13.074.003z" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium">{{ booking.provider.user?.name || 'Provider' }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ booking.provider.years_experience }} yrs exp
                                        · ⭐ {{ Number(booking.provider.average_rating).toFixed(1) }}
                                    </p>
                                </div>
                            </div>

                            <!-- Progress bar (non-cancelled) -->
                            <div v-if="booking.status !== 'cancelled'" class="space-y-1">
                                <div class="flex items-center gap-1.5">
                                    <div
                                        v-for="step in 4"
                                        :key="step"
                                        class="flex-1 h-1.5 rounded-full transition-colors"
                                        :class="statusStep(booking.status) >= step ? 'bg-primary' : 'bg-muted'"
                                    />
                                </div>
                                <div class="flex">
                                    <span
                                        v-for="(label, i) in stepLabels"
                                        :key="i"
                                        class="flex-1 text-[10px] text-center"
                                        :class="statusStep(booking.status) > i ? 'text-primary font-medium' : 'text-muted-foreground'"
                                    >{{ label }}</span>
                                </div>
                            </div>

                            <!-- Amount + actions -->
                            <div class="flex items-center justify-between gap-4 pt-2 border-t">
                                <div class="space-y-0.5">
                                    <p class="text-xl font-bold">{{ formatCurrency(booking.service_price) }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        Platform fee: {{ formatCurrency(booking.platform_fee) }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Button as="a" :href="`/shop/bookings/${booking.id}`" variant="outline" size="sm">
                                        View
                                    </Button>
                                    <Button
                                        v-if="booking.status === 'in_progress' || booking.status === 'confirmed'"
                                        size="sm"
                                        :disabled="completingBookingId === booking.id"
                                        @click="handleCompleteBooking(booking.id)"
                                    >
                                        Mark Complete
                                    </Button>
                                </div>
                            </div>

                            <!-- Rating prompt -->
                            <div
                                v-if="ratingBookingId === booking.id"
                                class="p-4 bg-muted/50 rounded-lg space-y-3"
                            >
                                <p class="text-sm font-medium">Rate this provider</p>
                                <div class="flex items-center gap-2">
                                    <button
                                        v-for="star in 5"
                                        :key="star"
                                        type="button"
                                        @click="rating = star"
                                        class="text-2xl transition-colors"
                                        :class="star <= rating ? 'text-yellow-500' : 'text-muted-foreground'"
                                    >★</button>
                                </div>
                                <div class="flex gap-2">
                                    <Button size="sm" @click="handleSubmitRating(booking.id)">Submit Rating</Button>
                                    <Button size="sm" variant="ghost" @click="ratingBookingId = null">Skip</Button>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <Card v-else>
                <CardContent class="flex flex-col items-center justify-center py-12 px-4 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-16 text-muted-foreground mb-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                    </svg>
                    <h3 class="text-lg font-semibold mb-2">No bookings found</h3>
                    <p class="text-sm text-muted-foreground max-w-md mb-4">
                        <template v-if="activeFilter === 'all'">
                            You don't have any bookings yet. Create a service request to get started.
                        </template>
                        <template v-else>
                            No {{ activeFilter }} bookings found.
                        </template>
                    </p>
                    <Button v-if="activeFilter === 'all'" as="a" href="/shop/service-requests/create">
                        Create Request
                    </Button>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div v-if="bookings.data.length > 0" class="text-center text-sm text-muted-foreground pb-4">
                Page {{ bookings.current_page }} of {{ bookings.last_page }}
            </div>
        </div>
    </AppLayout>
</template>
