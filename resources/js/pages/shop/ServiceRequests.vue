<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { ServiceRequest, Booking, PaginatedResponse } from '@/types/marketplace';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';
import PageHelp from '@/components/marketplace/PageHelp.vue';
import PageHeader from '@/components/ui/page-header/PageHeader.vue';
import ListItem from '@/components/marketplace/ListItem.vue';
import EmptyState from '@/components/ui/empty-state/EmptyState.vue';
import Icon from '@/components/ui/icon/Icon.vue';

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

const formatTime = (time: string) => {
    if (!time) return '';

    // Handle plain time strings like "08:00:00" or "08:00"
    const [hours, minutes] = time.split(':').map(Number);
    const period = hours >= 12 ? 'PM' : 'AM';
    const displayHours = hours % 12 || 12;

    return `${displayHours}:${minutes.toString().padStart(2, '0')} ${period}`;
};

const stepLabels = ['Pending', 'Confirmed', 'In Progress', 'Complete'];
const statusStep = (status: string) =>
    ({ pending: 1, confirmed: 2, in_progress: 3, completed: 4, cancelled: 0 }[status] ?? 0);
</script>

<template>
    <Head title="Service Requests" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
            <!-- Page Header -->
            <PageHeader
                title="Service Requests"
                :description="tab === 'bookings' ? 'Track your confirmed bookings with providers' : 'Manage your posted service requests'"
            >
                <template #action>
                    <Button v-if="tab === 'requests'" as="a" href="/shop/service-requests/create">
                        <Icon name="Plus" size="sm" class="mr-2" />
                        Create Request
                    </Button>
                </template>
            </PageHeader>

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
                <EmptyState
                    v-if="!requests"
                    icon="Store"
                    title="Set up your shop profile first"
                    description="Create your shop profile before posting service requests."
                    action-label="Set Up Shop Profile"
                    action-href="/settings/profile?tab=shop"
                />

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
                <div v-if="requests.data.length > 0" class="space-y-3">
                    <ListItem
                        v-for="request in requests.data"
                        :key="request.id"
                        status="default"
                    >
                        <template #header>
                            <h3 class="font-semibold text-base">{{ request.title }}</h3>
                        </template>

                        <template #content>
                            <div class="flex flex-col gap-1.5">
                                <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                    <Icon name="Calendar" size="xs" />
                                    <span>{{ formatDate(request.service_date || request.start_time) }}</span>
                                    <template v-if="request.start_time">
                                        <span>•</span>
                                        <Icon name="Clock" size="xs" />
                                        <span>{{ formatTime(request.start_time) }} – {{ formatTime(request.end_time || '') }}</span>
                                    </template>
                                </div>
                                <div v-if="request.shop_location" class="flex items-center gap-2 text-xs text-muted-foreground">
                                    <Icon name="MapPin" size="xs" />
                                    <span>{{ request.shop_location.address_line_1 }}, {{ request.shop_location.city }}, {{ request.shop_location.state }}</span>
                                </div>
                            </div>
                        </template>

                        <template #footer>
                            <div class="flex items-baseline gap-2 text-xs text-muted-foreground">
                                <span class="text-lg font-bold text-foreground">{{ formatCurrency(request.price) }}</span>
                                <span>· Expires {{ formatDate(request.expires_at) }}</span>
                            </div>
                        </template>

                        <template #badge>
                            <Badge :variant="request.status_variant">
                                {{ request.status_label }}
                            </Badge>
                        </template>

                        <template #action>
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
                        </template>
                    </ListItem>
                </div>

                <EmptyState
                    v-else
                    icon="FileX"
                    title="No requests found"
                    :description="activeFilter === 'all' ? 'You haven\'t posted any service requests yet.' : 'No requests match this filter.'"
                    :action-label="activeFilter === 'all' ? 'Create Your First Request' : undefined"
                    :action-href="activeFilter === 'all' ? '/shop/service-requests/create' : undefined"
                />

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
                <EmptyState
                    v-if="!bookings"
                    icon="Store"
                    title="Set up your shop profile first"
                    description="Create your shop profile to start posting service requests and receiving bookings."
                    action-label="Set Up Shop Profile"
                    action-href="/settings/profile?tab=shop"
                />

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
                <div v-if="bookings.data.length > 0" class="space-y-3">
                    <div v-for="booking in bookings.data" :key="booking.id" class="border rounded-lg p-5 space-y-4 transition-all hover:shadow-md hover:border-primary/50">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 min-w-0 space-y-1.5">
                                <h3 class="font-semibold text-base">{{ booking.service_request?.title || 'Service Booking' }}</h3>
                                <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                    <Icon name="Calendar" size="xs" />
                                    <span>{{ formatDate(booking.service_request?.service_date || '') }}</span>
                                    <template v-if="booking.service_request?.start_time">
                                        <span>•</span>
                                        <Icon name="Clock" size="xs" />
                                        <span>{{ formatTime(booking.service_request.start_time) }} – {{ formatTime(booking.service_request.end_time || '') }}</span>
                                    </template>
                                </div>
                            </div>
                            <Badge :variant="booking.status_variant">
                                {{ booking.status_label }}
                            </Badge>
                        </div>

                        <!-- Provider info -->
                        <div v-if="booking.provider" class="flex items-center gap-3 p-3 bg-muted/50 rounded-lg">
                            <div class="size-9 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                                <Icon name="User" size="sm" class="text-primary" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium">{{ booking.provider.user?.name || 'Provider' }}</p>
                                <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                    <Icon name="Briefcase" size="xs" />
                                    <span>{{ booking.provider.years_experience }} yrs exp</span>
                                    <span>•</span>
                                    <Icon name="Star" size="xs" class="fill-current" />
                                    <span>{{ Number(booking.provider.average_rating).toFixed(1) }}</span>
                                </div>
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
                        <div class="flex items-center justify-between gap-4 pt-3 border-t">
                            <div class="space-y-0.5">
                                <div class="flex items-baseline gap-1">
                                    <span class="text-xl font-bold text-foreground">{{ formatCurrency(booking.service_price) }}</span>
                                </div>
                                <div class="flex items-center gap-1 text-xs text-muted-foreground">
                                    <Icon name="Receipt" size="xs" />
                                    <span>Platform fee: {{ formatCurrency(booking.platform_fee) }}</span>
                                </div>
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
                </div>

                <EmptyState
                    v-else
                    icon="CalendarOff"
                    title="No bookings found"
                    :description="activeFilter === 'all' ? 'Bookings appear here when a provider accepts one of your service requests.' : 'No ' + activeFilter + ' bookings found.'"
                    :action-label="activeFilter === 'all' ? 'Create a Request' : undefined"
                    :action-href="activeFilter === 'all' ? '/shop/service-requests' : undefined"
                />

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
