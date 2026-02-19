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

const props = withDefaults(defineProps<Props>(), {
    filter: 'upcoming',
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'My Bookings',
        href: '/provider/bookings',
    },
];

const activeFilter = ref(props.filter);
const completingBookingId = ref<number | null>(null);

const filters = [
    { key: 'upcoming', label: 'Upcoming' },
    { key: 'in_progress', label: 'In Progress' },
    { key: 'completed', label: 'Completed' },
    { key: 'cancelled', label: 'Cancelled' },
];

const handleFilterChange = (filterKey: string) => {
    activeFilter.value = filterKey;
    router.get(
        '/provider/bookings',
        { filter: filterKey },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const handleMarkComplete = (bookingId: number) => {
    if (confirm('Mark this booking as complete?')) {
        completingBookingId.value = bookingId;
        router.post(
            `/provider/bookings/${bookingId}/complete`,
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    completingBookingId.value = null;
                },
            },
        );
    }
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
};

const formatDate = (date: string) => {
    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(new Date(date));
};

const formatTime = (time: string) => {
    return new Date(`2000-01-01T${time}`).toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
    });
};


</script>

<template>
    <Head title="My Bookings" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
            <!-- Header -->
            <div class="space-y-2">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                    My Bookings
                </h1>
                <p class="text-sm text-muted-foreground md:text-base">
                    Manage your accepted service bookings
                </p>
                <PageHelp storage-key="provider-my-bookings" :steps="['These are your accepted bookings — shifts you\'ve agreed to work.', 'Show up on time and complete the work. Mark the booking complete when done.', 'Your payout is released after the booking is marked complete by either party.', 'Rate the shop after each completed booking to build the community.']" />
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

            <!-- Bookings List -->
            <div v-if="bookings.data.length > 0" class="space-y-4">
                <Card v-for="booking in bookings.data" :key="booking.id">
                    <CardContent class="pt-6">
                        <div class="flex flex-col gap-4">
                            <!-- Header -->
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1 min-w-0 space-y-1">
                                    <h3 class="font-semibold text-lg">
                                        {{
                                            booking.service_request?.title ||
                                            'Service Booking'
                                        }}
                                    </h3>
                                    <p class="text-sm text-muted-foreground">
                                        {{
                                            formatDate(
                                                booking.service_request
                                                    ?.service_date || '',
                                            )
                                        }}
                                        •
                                        {{
                                            formatTime(
                                                booking.service_request
                                                    ?.start_time || '',
                                            )
                                        }}
                                        -
                                        {{
                                            formatTime(
                                                booking.service_request
                                                    ?.end_time || '',
                                            )
                                        }}
                                    </p>
                                </div>
                                <Badge :variant="booking.status_variant">
                                    {{ booking.status_label }}
                                </Badge>
                            </div>

                            <!-- Shop Location Info -->
                            <div
                                v-if="booking.service_request?.shop_location"
                                class="flex items-start gap-3 p-3 bg-muted/50 rounded-lg"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                    class="size-5 shrink-0 mt-0.5 text-muted-foreground"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 103 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 002.273 1.765 11.842 11.842 0 00.976.544l.062.029.018.008.006.003zM10 11.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium">
                                        {{
                                            booking.service_request.shop_location
                                                .address_line_1
                                        }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{
                                            booking.service_request.shop_location
                                                .city
                                        }},
                                        {{
                                            booking.service_request.shop_location
                                                .state
                                        }}
                                        {{
                                            booking.service_request.shop_location
                                                .zip_code
                                        }}
                                    </p>
                                </div>
                            </div>

                            <!-- Earnings -->
                            <div class="flex items-center justify-between gap-4 pt-2 border-t">
                                <div class="space-y-1">
                                    <p class="text-sm text-muted-foreground">
                                        You will earn
                                    </p>
                                    <p class="text-2xl font-bold">
                                        {{ formatCurrency(booking.provider_payout) }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Button
                                        as="a"
                                        :href="`/provider/bookings/${booking.id}`"
                                        variant="outline"
                                        size="sm"
                                    >
                                        View
                                    </Button>
                                    <Button
                                        v-if="
                                            booking.status === 'in_progress' ||
                                            booking.status === 'confirmed'
                                        "
                                        size="sm"
                                        :disabled="
                                            completingBookingId === booking.id
                                        "
                                        @click="handleMarkComplete(booking.id)"
                                    >
                                        Mark Complete
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <Card v-else>
                <CardContent class="flex flex-col items-center justify-center py-12 px-4 text-center">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                        class="size-16 text-muted-foreground mb-4"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"
                        />
                    </svg>
                    <h3 class="text-lg font-semibold mb-2">No bookings found</h3>
                    <p class="text-sm text-muted-foreground max-w-md mb-4">
                        You don't have any bookings in this category. Browse
                        available service requests to get started.
                    </p>
                    <Button as="a" href="/provider/available-requests">
                        View Available Requests
                    </Button>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div
                v-if="bookings.data.length > 0"
                class="text-center text-sm text-muted-foreground pb-4"
            >
                Page {{ bookings.current_page }} of {{ bookings.last_page }}
            </div>
        </div>
    </AppLayout>
</template>
