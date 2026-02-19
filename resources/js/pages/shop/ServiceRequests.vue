<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { ServiceRequest, Booking, PaginatedResponse } from '@/types/marketplace';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';
import PageHelp from '@/components/marketplace/PageHelp.vue';

interface Props {
    tab?: string;
    filter?: string;
    requests: PaginatedResponse<ServiceRequest> | null;
    bookings: PaginatedResponse<Booking> | null;
}

const props = withDefaults(defineProps<Props>(), {
    tab: 'requests',
    filter: 'all',
});

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Service Requests', href: '/shop/service-requests' }];

const activeFilter = ref(props.filter);
const cancellingRequestId = ref<number | null>(null);
const completingBookingId = ref<number | null>(null);
const ratingBookingId = ref<number | null>(null);
const rating = ref(0);

// ─── Tab switching ────────────────────────────────────────────────────────────

const switchTab = (tab: string) => {
    router.get('/shop/service-requests', { tab, filter: 'all' }, { preserveState: false });
};

// ─── Filter switching ─────────────────────────────────────────────────────────

const requestFilters = [
    { key: 'all', label: 'All' },
    { key: 'open', label: 'Open' },
    { key: 'filled', label: 'Booked' },
    { key: 'expired', label: 'Expired' },
    { key: 'cancelled', label: 'Cancelled' },
];

const bookingFilters = [
    { key: 'all', label: 'All' },
    { key: 'active', label: 'Active' },
    { key: 'completed', label: 'Completed' },
    { key: 'cancelled', label: 'Cancelled' },
];

const handleFilterChange = (filterKey: string) => {
    activeFilter.value = filterKey;
    router.get(
        '/shop/service-requests',
        { tab: props.tab, filter: filterKey },
        { preserveState: true, preserveScroll: true },
    );
};

// ─── Service request actions ──────────────────────────────────────────────────

const handleCancelRequest = (requestId: number) => {
    if (confirm('Are you sure you want to cancel this service request? This action cannot be undone.')) {
        cancellingRequestId.value = requestId;
        router.post(`/shop/service-requests/${requestId}/cancel`, {}, {
            preserveScroll: true,
            onFinish: () => { cancellingRequestId.value = null; },
        });
    }
};

const handleLoadMoreRequests = () => {
    if (props.requests && props.requests.current_page < props.requests.last_page) {
        router.get('/shop/service-requests', {
            tab: 'requests',
            filter: activeFilter.value,
            page: props.requests.current_page + 1,
        }, { preserveState: true, preserveScroll: true, only: ['requests'] });
    }
};

// ─── Booking actions ──────────────────────────────────────────────────────────

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
    if (rating.value === 0) { alert('Please select a rating'); return; }
    router.post(`/shop/bookings/${bookingId}/rate`, { rating: rating.value }, {
        preserveScroll: true,
        onSuccess: () => { ratingBookingId.value = null; rating.value = 0; },
    });
};

const handleLoadMoreBookings = () => {
    if (props.bookings && props.bookings.current_page < props.bookings.last_page) {
        router.get('/shop/service-requests', {
            tab: 'bookings',
            filter: activeFilter.value,
            page: props.bookings.current_page + 1,
        }, { preserveState: true, preserveScroll: true, only: ['bookings'] });
    }
};

// ─── Formatters ───────────────────────────────────────────────────────────────

const formatCurrency = (amount: number) =>
    new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);

const formatDate = (date: string) =>
    new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(date));

const formatTime = (time: string) =>
    new Date(time).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });

const stepLabels = ['Pending', 'Confirmed', 'In Progress', 'Complete'];
const statusStep = (status: string) =>
    ({ pending: 1, confirmed: 2, in_progress: 3, completed: 4, cancelled: 0 }[status] ?? 0);
</script>

<template>
    <Head title="Service Requests" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1">
                    <h1 class="text-2xl font-bold tracking-tight md:text-3xl">Service Requests</h1>
                    <p class="text-sm text-muted-foreground md:text-base">
                        {{ tab === 'bookings' ? 'Track your confirmed bookings with providers' : 'Manage your posted service requests' }}
                    </p>
                </div>
                <Button v-if="tab === 'requests'" as="a" href="/shop/service-requests/create" class="w-full sm:w-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5 mr-2">
                        <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                    </svg>
                    Create Request
                </Button>
            </div>

            <!-- Tabs -->
            <div class="flex gap-1 border-b">
                <button
                    class="px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px"
                    :class="tab === 'requests'
                        ? 'border-primary text-foreground'
                        : 'border-transparent text-muted-foreground hover:text-foreground'"
                    @click="switchTab('requests')"
                >
                    Requests
                </button>
                <button
                    class="px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px"
                    :class="tab === 'bookings'
                        ? 'border-primary text-foreground'
                        : 'border-transparent text-muted-foreground hover:text-foreground'"
                    @click="switchTab('bookings')"
                >
                    Bookings
                </button>
            </div>

            <!-- ── REQUESTS TAB ── -->
            <template v-if="tab === 'requests'">
                <!-- No shop profile -->
                <Card v-if="!requests">
                    <CardContent class="flex flex-col items-center justify-center py-12 px-4 text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-16 text-muted-foreground mb-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016 2.993 2.993 0 002.25-1.016 3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 2.189a3 3 0 01-.621 4.72m-16.5 0h16.5" />
                        </svg>
                        <h3 class="text-lg font-semibold mb-2">Set up your shop profile first</h3>
                        <p class="text-sm text-muted-foreground max-w-md mb-4">
                            Create your shop profile before posting service requests.
                        </p>
                        <Button as="a" href="/shop/profile/edit">Set Up Shop Profile</Button>
                    </CardContent>
                </Card>

                <template v-else>
                <PageHelp storage-key="shop-service-requests" :steps="[
                    'Click \'Create Request\' to post a request for a specific shift at one of your locations.',
                    'Open requests are visible to active providers — a provider can accept it at any time.',
                    'Once accepted, the request status changes to \'Booked\' and a booking is created automatically.',
                    'You can cancel an open request at any time before a provider accepts it.',
                ]" />

                <!-- Filter badges -->
                <div class="flex gap-2 overflow-x-auto pb-1 -mx-4 px-4 md:mx-0 md:px-0">
                    <Badge
                        v-for="f in requestFilters"
                        :key="f.key"
                        :variant="activeFilter === f.key ? 'default' : 'outline'"
                        as="button"
                        class="cursor-pointer whitespace-nowrap px-4 py-2 text-sm font-medium transition-colors touch-manipulation"
                        @click="handleFilterChange(f.key)"
                    >
                        {{ f.label }}
                    </Badge>
                </div>

                <div class="text-sm text-muted-foreground">
                    Showing {{ requests.data.length }} of {{ requests.total }} requests
                </div>

                <!-- List -->
                <div v-if="requests.data.length > 0" class="space-y-4">
                    <Card v-for="request in requests.data" :key="request.id">
                        <CardContent class="pt-6">
                            <div class="flex flex-col gap-3">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-semibold text-lg leading-snug">{{ request.title }}</h3>
                                        <p class="text-sm text-muted-foreground mt-0.5">
                                            {{ formatDate(request.service_date || request.start_time) }}
                                            <template v-if="request.start_time">
                                                · {{ formatTime(request.start_time) }} – {{ formatTime(request.end_time || '') }}
                                            </template>
                                        </p>
                                    </div>
                                    <Badge :variant="request.status_variant">
                                        {{ request.status_label }}
                                    </Badge>
                                </div>

                                <div v-if="request.shop_location" class="flex items-center gap-2 text-sm text-muted-foreground">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4 shrink-0">
                                        <path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 103 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 002.273 1.765 11.842 11.842 0 00.976.544l.062.029.018.008.006.003zM10 11.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" clip-rule="evenodd" />
                                    </svg>
                                    {{ request.shop_location.address_line_1 }}, {{ request.shop_location.city }}, {{ request.shop_location.state }}
                                </div>

                                <div class="flex items-center justify-between gap-4 pt-2 border-t">
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-xl font-bold">{{ formatCurrency(request.price) }}</span>
                                        <span class="text-xs text-muted-foreground">Expires {{ formatDate(request.expires_at) }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Button as="a" :href="`/shop/service-requests/${request.id}`" variant="outline" size="sm">View</Button>
                                        <Button
                                            v-if="request.status === 'open'"
                                            variant="destructive"
                                            size="sm"
                                            :disabled="cancellingRequestId === request.id"
                                            @click="handleCancelRequest(request.id)"
                                        >
                                            Cancel
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card v-else>
                    <CardContent class="flex flex-col items-center justify-center py-12 px-4 text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-16 text-muted-foreground mb-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                        </svg>
                        <h3 class="text-lg font-semibold mb-2">No requests found</h3>
                        <p class="text-sm text-muted-foreground max-w-md mb-4">
                            {{ activeFilter === 'all' ? 'You haven\'t posted any service requests yet.' : 'No requests match this filter.' }}
                        </p>
                        <Button as="a" href="/shop/service-requests/create">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5 mr-2">
                                <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                            </svg>
                            Create Your First Request
                        </Button>
                    </CardContent>
                </Card>

                <div v-if="requests.current_page < requests.last_page" class="flex justify-center pt-2">
                    <Button variant="outline" @click="handleLoadMoreRequests">Load More</Button>
                </div>
                <div v-if="requests.data.length > 0" class="text-center text-sm text-muted-foreground pb-4">
                    Page {{ requests.current_page }} of {{ requests.last_page }}
                </div>
                </template>
            </template>

            <!-- ── BOOKINGS TAB ── -->
            <template v-if="tab === 'bookings'">
                <!-- No shop profile -->
                <Card v-if="!bookings">
                    <CardContent class="flex flex-col items-center justify-center py-12 px-4 text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-16 text-muted-foreground mb-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016 2.993 2.993 0 002.25-1.016 3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 2.189a3 3 0 01-.621 4.72m-16.5 0h16.5" />
                        </svg>
                        <h3 class="text-lg font-semibold mb-2">Set up your shop profile first</h3>
                        <p class="text-sm text-muted-foreground max-w-md mb-4">
                            Create your shop profile to start posting service requests and receiving bookings.
                        </p>
                        <Button as="a" href="/shop/profile/edit">Set Up Shop Profile</Button>
                    </CardContent>
                </Card>

                <template v-else>
                <PageHelp storage-key="shop-bookings" :steps="[
                    'Bookings are created automatically when a provider accepts one of your service requests.',
                    'A booking starts as \'Pending\' until payment is processed, then becomes \'Confirmed\'.',
                    'After the shift is complete, mark the booking as complete and rate the provider.',
                    'Use the filter tabs to view active, completed, or cancelled bookings.',
                ]" />

                <!-- Filter badges -->
                <div class="flex gap-2 overflow-x-auto pb-1 -mx-4 px-4 md:mx-0 md:px-0">
                    <Badge
                        v-for="f in bookingFilters"
                        :key="f.key"
                        :variant="activeFilter === f.key ? 'default' : 'outline'"
                        as="button"
                        class="cursor-pointer whitespace-nowrap px-4 py-2 text-sm font-medium transition-colors touch-manipulation"
                        @click="handleFilterChange(f.key)"
                    >
                        {{ f.label }}
                    </Badge>
                </div>

                <div class="text-sm text-muted-foreground">
                    Showing {{ bookings.data.length }} of {{ bookings.total }} bookings
                </div>

                <!-- List -->
                <div v-if="bookings.data.length > 0" class="space-y-4">
                    <Card v-for="booking in bookings.data" :key="booking.id">
                        <CardContent class="pt-6">
                            <div class="flex flex-col gap-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1 min-w-0 space-y-1">
                                        <h3 class="font-semibold text-lg">{{ booking.service_request?.title || 'Service Booking' }}</h3>
                                        <p class="text-sm text-muted-foreground">
                                            {{ formatDate(booking.service_request?.service_date || '') }}
                                            <template v-if="booking.service_request?.start_time">
                                                · {{ formatTime(booking.service_request.start_time) }} – {{ formatTime(booking.service_request.end_time || '') }}
                                            </template>
                                        </p>
                                    </div>
                                    <Badge :variant="booking.status_variant">
                                        {{ booking.status_label }}
                                    </Badge>
                                </div>

                                <!-- Provider info -->
                                <div v-if="booking.provider" class="flex items-center gap-3 p-3 bg-muted/50 rounded-lg">
                                    <div class="size-9 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5 text-primary">
                                            <path d="M10 8a3 3 0 100-6 3 3 0 000 6zM3.465 14.493a1.23 1.23 0 00.41 1.412A9.957 9.957 0 0010 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 00-13.074.003z" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium">{{ booking.provider.user?.name || 'Provider' }}</p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ booking.provider.years_experience }} yrs exp · ⭐ {{ Number(booking.provider.average_rating).toFixed(1) }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Progress bar -->
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
                                        <p class="text-xs text-muted-foreground">Platform fee: {{ formatCurrency(booking.platform_fee) }}</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Button as="a" :href="`/shop/bookings/${booking.id}`" variant="outline" size="sm">View</Button>
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
                                <div v-if="ratingBookingId === booking.id" class="p-4 bg-muted/50 rounded-lg space-y-3">
                                    <p class="text-sm font-medium">Rate this provider</p>
                                    <div class="flex items-center gap-2">
                                        <button
                                            v-for="star in 5"
                                            :key="star"
                                            type="button"
                                            class="text-2xl transition-colors"
                                            :class="star <= rating ? 'text-yellow-500' : 'text-muted-foreground'"
                                            @click="rating = star"
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

                <Card v-else>
                    <CardContent class="flex flex-col items-center justify-center py-12 px-4 text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-16 text-muted-foreground mb-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                        </svg>
                        <h3 class="text-lg font-semibold mb-2">No bookings found</h3>
                        <p class="text-sm text-muted-foreground max-w-md mb-4">
                            {{ activeFilter === 'all' ? 'Bookings appear here when a provider accepts one of your service requests.' : 'No ' + activeFilter + ' bookings found.' }}
                        </p>
                        <Button v-if="activeFilter === 'all'" as="a" href="/shop/service-requests">Create a Request</Button>
                    </CardContent>
                </Card>

                <div v-if="bookings.current_page < bookings.last_page" class="flex justify-center pt-2">
                    <Button variant="outline" @click="handleLoadMoreBookings">Load More</Button>
                </div>
                <div v-if="bookings.data.length > 0" class="text-center text-sm text-muted-foreground pb-4">
                    Page {{ bookings.current_page }} of {{ bookings.last_page }}
                </div>
                </template>
            </template>
        </div>
    </AppLayout>
</template>
