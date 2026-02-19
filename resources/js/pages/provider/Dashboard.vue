<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { Booking } from '@/types/marketplace';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';
import PageHelp from '@/components/marketplace/PageHelp.vue';

interface EarningsStats {
    this_week: number;
    this_month: number;
    all_time: number;
}

interface ProviderStats {
    completed_jobs: number;
    average_rating: number;
    total_earnings: number;
    pending_invitations: number;
}

interface Props {
    earnings: EarningsStats;
    stats: ProviderStats;
    upcoming_bookings: Booking[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Provider Dashboard',
        href: '/provider/dashboard',
    },
];

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
    return new Intl.DateTimeFormat('en-US', {
        hour: 'numeric',
        minute: '2-digit',
    }).format(new Date(time));
};
</script>

<template>
    <Head title="Provider Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
            <!-- Header -->
            <div class="space-y-2">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                    Provider Dashboard
                </h1>
                <p class="text-sm text-muted-foreground md:text-base">
                    Track your earnings and upcoming bookings
                </p>
                <PageHelp storage-key="provider-dashboard" :steps="['Browse available service requests from coffee shops in the marketplace.', 'Accept a request to create a booking — show up, do great work, get paid.', 'Your earnings are paid out after the shop marks the booking complete.', 'Keep your profile complete and ratings high to appear more attractive to shops.']" />
            </div>

            <!-- Earnings Summary -->
            <div class="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardDescription>This Week</CardDescription>
                        <CardTitle class="text-3xl">
                            {{ formatCurrency(earnings.this_week) }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-muted-foreground">
                            Earnings from this week
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardDescription>This Month</CardDescription>
                        <CardTitle class="text-3xl">
                            {{ formatCurrency(earnings.this_month) }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-muted-foreground">
                            Earnings from this month
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardDescription>All Time</CardDescription>
                        <CardTitle class="text-3xl">
                            {{ formatCurrency(earnings.all_time) }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-muted-foreground">
                            Total lifetime earnings
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Quick Stats -->
            <div class="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader>
                        <CardDescription>Completed Jobs</CardDescription>
                        <CardTitle class="text-2xl">
                            {{ stats.completed_jobs }}
                        </CardTitle>
                    </CardHeader>
                </Card>

                <Card>
                    <CardHeader>
                        <CardDescription>Average Rating</CardDescription>
                        <CardTitle class="text-2xl">
                            {{ Number(stats.average_rating).toFixed(1) }} ⭐
                        </CardTitle>
                    </CardHeader>
                </Card>

                <Card>
                    <CardHeader>
                        <CardDescription>Total Earnings</CardDescription>
                        <CardTitle class="text-2xl">
                            {{ formatCurrency(stats.total_earnings) }}
                        </CardTitle>
                    </CardHeader>
                </Card>

                <Card>
                    <CardHeader>
                        <CardDescription>Pending Invitations</CardDescription>
                        <CardTitle class="text-2xl">
                            {{ stats.pending_invitations }}
                        </CardTitle>
                    </CardHeader>
                </Card>
            </div>

            <!-- Quick Actions -->
            <div class="flex flex-col gap-3 sm:flex-row">
                <Button
                    as="a"
                    href="/provider/available-requests"
                    class="w-full sm:w-auto"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                        class="size-5 mr-2"
                    >
                        <path
                            d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z"
                        />
                    </svg>
                    View Available Requests
                </Button>
                <Button
                    as="a"
                    href="/provider/bookings"
                    variant="outline"
                    class="w-full sm:w-auto"
                >
                    My Bookings
                </Button>
            </div>

            <!-- Upcoming Bookings Calendar View -->
            <Card>
                <CardHeader>
                    <CardTitle>Upcoming Bookings</CardTitle>
                    <CardDescription>
                        Your scheduled bookings for the next 7 days
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div v-if="upcoming_bookings.length > 0" class="space-y-3">
                        <div
                            v-for="booking in upcoming_bookings"
                            :key="booking.id"
                            class="flex flex-col gap-3 p-4 border rounded-lg hover:bg-accent/50 transition-colors"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-semibold truncate">
                                        {{
                                            booking.service_request?.title ||
                                            'Service'
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
                                <Badge variant="default">
                                    {{ booking.status.replace('_', ' ') }}
                                </Badge>
                            </div>
                            <div
                                v-if="
                                    booking.service_request?.shop_location
                                "
                                class="flex items-start gap-2 text-sm text-muted-foreground"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                    class="size-5 shrink-0"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 103 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 002.273 1.765 11.842 11.842 0 00.976.544l.062.029.018.008.006.003zM10 11.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                <span>
                                    {{
                                        booking.service_request.shop_location
                                            .address_line_1
                                    }},
                                    {{
                                        booking.service_request.shop_location
                                            .city
                                    }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between pt-2 border-t">
                                <p class="text-lg font-bold">
                                    {{ formatCurrency(booking.provider_payout) }}
                                </p>
                                <Button
                                    as="a"
                                    :href="`/provider/bookings/${booking.id}`"
                                    variant="outline"
                                    size="sm"
                                >
                                    View Details
                                </Button>
                            </div>
                        </div>
                    </div>
                    <div
                        v-else
                        class="text-center py-8 text-sm text-muted-foreground"
                    >
                        No upcoming bookings
                    </div>
                    <Button
                        as="a"
                        href="/provider/bookings"
                        variant="outline"
                        class="w-full"
                    >
                        View All Bookings
                    </Button>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
