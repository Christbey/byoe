<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { ServiceRequest, PaginatedResponse } from '@/types/marketplace';
import ServiceRequestCard from '@/components/marketplace/ServiceRequestCard.vue';
import MobileTapButton from '@/components/marketplace/MobileTapButton.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import { Button } from '@/components/ui/button';
import PageHelp from '@/components/marketplace/PageHelp.vue';

interface Props {
    requests: PaginatedResponse<ServiceRequest>;
    filter?: string;
    locationSource?: 'gps' | 'zip' | null;
}

const props = withDefaults(defineProps<Props>(), {
    filter: 'all',
});

const loadingRequestId = ref<number | null>(null);
const confirmingRequest = ref<ServiceRequest | null>(null);
const isRequestingGps = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Available Requests', href: '/provider/available-requests' },
];

const activeFilter = ref(props.filter);

const filters = [
    { key: 'all', label: 'All Requests' },
    { key: 'today', label: 'Today' },
    { key: 'week', label: 'This Week' },
    { key: 'nearby', label: 'Nearby' },
];

const handleFilterChange = (filterKey: string) => {
    activeFilter.value = filterKey;

    if (filterKey === 'nearby' && 'geolocation' in navigator) {
        isRequestingGps.value = true;
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                isRequestingGps.value = false;
                router.get('/provider/available-requests', {
                    filter: 'nearby',
                    lat: pos.coords.latitude,
                    lng: pos.coords.longitude,
                }, { preserveState: true, preserveScroll: true });
            },
            () => {
                isRequestingGps.value = false;
                router.get('/provider/available-requests', { filter: 'nearby' },
                    { preserveState: true, preserveScroll: true });
            },
            { timeout: 10000 }
        );
        return;
    }

    router.get('/provider/available-requests', { filter: filterKey }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const openConfirm = (request: ServiceRequest) => {
    confirmingRequest.value = request;
};

const cancelConfirm = () => {
    confirmingRequest.value = null;
};

const confirmAccept = () => {
    if (!confirmingRequest.value) return;
    const requestId = confirmingRequest.value.id;
    confirmingRequest.value = null;
    loadingRequestId.value = requestId;

    router.post(`/provider/requests/${requestId}/accept`, {}, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => { loadingRequestId.value = null; },
    });
};

const handleLoadMore = () => {
    if (props.requests.current_page < props.requests.last_page) {
        router.get('/provider/available-requests', {
            filter: activeFilter.value,
            page: props.requests.current_page + 1,
        }, {
            preserveState: true,
            preserveScroll: true,
            only: ['requests'],
        });
    }
};

const hasMorePages = computed(() => props.requests.current_page < props.requests.last_page);

const formatCurrency = (amount: number) =>
    new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);

const formatDate = (date: string) =>
    new Intl.DateTimeFormat('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' }).format(new Date(date));

const formatTime = (time: string) =>
    new Date(time).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });

// Lock body scroll while modal is open
watch(confirmingRequest, (val) => {
    document.body.style.overflow = val ? 'hidden' : '';
});

// Close on Escape
const handleKeydown = (e: KeyboardEvent) => {
    if (e.key === 'Escape') cancelConfirm();
};
onMounted(() => window.addEventListener('keydown', handleKeydown));
onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
    document.body.style.overflow = '';
});
</script>

<template>
    <Head title="Available Requests" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
            <!-- Header -->
            <div class="space-y-2">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                    Available Service Requests
                </h1>
                <p class="text-sm text-muted-foreground md:text-base">
                    Browse and accept service requests from local coffee shops
                </p>
                <PageHelp storage-key="provider-available-requests" :steps="['These are open shifts posted by coffee shops — each one is a fixed-price service opportunity.', 'Review the date, time, location, skills required, and pay before accepting.', 'Tap \'Accept Request\' to claim the shift. You\'re an independent contractor — no obligation to accept any job.', 'Once you accept, a booking is created and the shop will be notified.']" />
            </div>

            <!-- Filter Tabs -->
            <div class="flex gap-2 overflow-x-auto pb-2 -mx-4 px-4 md:mx-0 md:px-0">
                <Badge
                    v-for="filter in filters"
                    :key="filter.key"
                    :variant="activeFilter === filter.key ? 'default' : 'outline'"
                    as="button"
                    @click="handleFilterChange(filter.key)"
                    class="cursor-pointer whitespace-nowrap px-4 py-2 text-sm font-medium transition-colors touch-manipulation"
                >
                    {{ filter.label }}
                </Badge>
            </div>

            <!-- Location Banner (Nearby filter) -->
            <div v-if="isRequestingGps" class="flex items-center gap-2 text-sm text-muted-foreground">
                <svg class="size-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                Requesting your location...
            </div>
            <div
                v-else-if="activeFilter === 'nearby' && locationSource"
                class="flex items-center gap-2 text-sm text-muted-foreground rounded-lg bg-muted/50 px-3 py-2"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4 shrink-0">
                    <path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 103 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 002.273 1.765 11.842 11.842 0 00.976.544l.062.029.018.008.006.003zM10 11.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" clip-rule="evenodd" />
                </svg>
                <span v-if="locationSource === 'gps'">Showing nearby requests using your current location</span>
                <span v-else>Showing nearby requests using your saved zip code</span>
            </div>
            <div
                v-else-if="activeFilter === 'nearby' && !locationSource && !isRequestingGps"
                class="rounded-lg border border-amber-300 bg-amber-50 dark:bg-amber-950/20 p-3 text-sm text-amber-800 dark:text-amber-300"
            >
                Could not determine your location. Enable GPS or add a zip code to your
                <a href="/provider/profile/edit" class="underline font-medium">provider profile</a>.
            </div>

            <!-- Results Count -->
            <div class="text-sm text-muted-foreground">
                Showing {{ requests.data.length }} of {{ requests.total }} requests
            </div>

            <!-- Service Request Cards -->
            <div v-if="requests.data.length > 0" class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <ServiceRequestCard
                    v-for="request in requests.data"
                    :key="request.id"
                    :service-request="request"
                    :distance="filter === 'nearby' ? request.distance ?? undefined : undefined"
                    class="flex flex-col"
                >
                    <MobileTapButton
                        variant="primary"
                        :loading="loadingRequestId === request.id"
                        :disabled="loadingRequestId !== null"
                        @click="openConfirm(request)"
                        class="w-full"
                    >
                        <template #loading>Accepting...</template>
                        Accept Request
                    </MobileTapButton>
                </ServiceRequestCard>
            </div>

            <!-- Empty State -->
            <div v-else class="flex flex-col items-center justify-center py-12 px-4 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-16 text-muted-foreground mb-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                </svg>
                <h3 class="text-lg font-semibold mb-2">No requests found</h3>
                <p class="text-sm text-muted-foreground max-w-md">
                    There are no service requests matching your current filter. Try selecting a different filter or check back later.
                </p>
            </div>

            <!-- Load More -->
            <div v-if="hasMorePages" class="flex justify-center pt-4">
                <MobileTapButton variant="secondary" @click="handleLoadMore" class="max-w-xs">
                    Load More
                </MobileTapButton>
            </div>

            <!-- Pagination Info -->
            <div v-if="requests.data.length > 0" class="text-center text-sm text-muted-foreground pb-4">
                Page {{ requests.current_page }} of {{ requests.last_page }}
            </div>
        </div>

        <!-- ── Confirmation Modal ─────────────────────────────────────── -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-150"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="confirmingRequest"
                    class="fixed inset-0 z-50"
                >
                    <!-- Backdrop -->
                    <div class="absolute inset-0 bg-black/50" @click="cancelConfirm" />

                    <!-- Centered dialog -->
                    <div class="relative flex min-h-full items-center justify-center p-4">
                        <div class="relative w-full max-w-md rounded-2xl bg-background p-6 shadow-xl">
                            <h2 class="text-lg font-bold mb-1">Accept This Request?</h2>
                            <p class="text-sm text-muted-foreground mb-4">
                                By accepting, you are committing to show up and complete this shift as an independent contractor.
                            </p>

                            <!-- Request Summary -->
                            <div class="rounded-lg border bg-muted/30 p-4 space-y-2 text-sm mb-6">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-base">{{ confirmingRequest.title }}</span>
                                    <span class="font-bold text-primary text-base">{{ formatCurrency(confirmingRequest.price) }}</span>
                                </div>
                                <p class="text-muted-foreground">
                                    {{ confirmingRequest.shop_location?.shop?.name ?? 'Coffee Shop' }}
                                    · {{ confirmingRequest.shop_location?.city }}, {{ confirmingRequest.shop_location?.state }}
                                </p>
                                <p>{{ formatDate(confirmingRequest.service_date) }}</p>
                                <p>{{ formatTime(confirmingRequest.start_time) }} – {{ formatTime(confirmingRequest.end_time) }}</p>
                            </div>

                            <!-- Compliance notice -->
                            <p class="text-xs text-muted-foreground mb-5 leading-relaxed">
                                You are accepting this work as an independent contractor (1099). You retain full control over how you perform the service.
                            </p>

                            <div class="flex flex-col gap-2 sm:flex-row-reverse">
                                <Button variant="success" class="w-full sm:w-auto sm:flex-1" @click="confirmAccept">
                                    Yes, Accept Request
                                </Button>
                                <Button variant="outline" class="w-full sm:w-auto sm:flex-1" @click="cancelConfirm">
                                    Cancel
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>
