<script setup lang="ts">
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { ServiceRequest, Booking } from '@/types/marketplace';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';
import PageHelp from '@/components/marketplace/PageHelp.vue';

interface DashboardStats {
    active_requests: number;
    upcoming_bookings: number;
    total_spent: number;
}

interface Props {
    shopName: string | null;
    stats: DashboardStats;
    recent_requests: ServiceRequest[];
    upcoming_bookings: Booking[];
}

const props = defineProps<Props>();

const pageTitle = computed(() => (props.shopName ? `${props.shopName} Dashboard` : 'Shop Dashboard'));

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Shop Dashboard',
        href: '/shop/dashboard',
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
    return new Date(`2000-01-01T${time}`).toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
    });
};

const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
        open: 'info',
        filled: 'outline',
        expired: 'destructive',
        cancelled: 'outline',
        pending: 'warning',
        confirmed: 'info',
        in_progress: 'info',
        completed: 'success',
    };
    return colors[status] || 'outline';
};

const requestStatusLabel = (status: string) => {
    const labels: Record<string, string> = {
        open: 'Open',
        filled: 'Booked',
        expired: 'Expired',
        cancelled: 'Cancelled',
    };
    return labels[status] ?? status;
};

const bookingStatusLabel = (status: string) => {
    const labels: Record<string, string> = {
        pending: 'Pending',
        confirmed: 'Confirmed',
        in_progress: 'In Progress',
        completed: 'Completed',
        cancelled: 'Cancelled',
    };
    return labels[status] ?? status;
};
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
            <!-- Header -->
            <div class="space-y-2">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                    {{ pageTitle }}
                </h1>
                <p class="text-sm text-muted-foreground md:text-base">
                    Overview of your service requests and bookings
                </p>
                <PageHelp storage-key="shop-dashboard" :steps="['Post a service request to find a worker — set the date, time, skills needed, and pay rate.', 'Providers in your area will see and accept your request. You\'ll see the booking appear here.', 'Once the provider completes the shift, mark it complete and leave a rating.', 'Track your upcoming bookings and spending on this dashboard.']" />
            </div>

            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardDescription>Active Requests</CardDescription>
                        <CardTitle class="text-3xl">
                            {{ stats.active_requests }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-muted-foreground">
                            Open service requests
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardDescription>Upcoming Bookings</CardDescription>
                        <CardTitle class="text-3xl">
                            {{ stats.upcoming_bookings }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-muted-foreground">
                            Next 7 days
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardDescription>Total Spent</CardDescription>
                        <CardTitle class="text-3xl">
                            {{ formatCurrency(stats.total_spent) }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-muted-foreground">
                            All time
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Quick Actions -->
            <div class="flex flex-col gap-3 sm:flex-row">
                <Button
                    as="a"
                    href="/shop/service-requests/create"
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
                    Create Request
                </Button>
                <Button
                    as="a"
                    href="/shop/bookings"
                    variant="outline"
                    class="w-full sm:w-auto"
                >
                    View All Bookings
                </Button>
            </div>

            <!-- Two Column Layout for Lists -->
            <div class="grid gap-4 lg:grid-cols-2">
                <!-- Recent Service Requests -->
                <Card>
                    <CardHeader>
                        <CardTitle>Recent Service Requests</CardTitle>
                        <CardDescription>
                            Your last 5 service requests
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div
                            v-if="recent_requests.length > 0"
                            class="space-y-3"
                        >
                            <div
                                v-for="request in recent_requests"
                                :key="request.id"
                                class="flex flex-col gap-2 p-3 border rounded-lg hover:bg-accent/50 transition-colors"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-sm truncate">
                                            {{ request.title }}
                                        </p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ formatDate(request.service_date) }}
                                            •
                                            {{ formatTime(request.start_time) }}
                                            -
                                            {{ formatTime(request.end_time) }}
                                        </p>
                                    </div>
                                    <Badge
                                        :variant="getStatusColor(request.status)"
                                        class="shrink-0"
                                    >
                                        {{ requestStatusLabel(request.status) }}
                                    </Badge>
                                </div>
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-semibold">
                                        {{ formatCurrency(request.price) }}
                                    </p>
                                    <Button
                                        as="a"
                                        :href="`/shop/service-requests/${request.id}`"
                                        variant="ghost"
                                        size="sm"
                                    >
                                        View
                                    </Button>
                                </div>
                            </div>
                        </div>
                        <div
                            v-else
                            class="text-center py-8 text-sm text-muted-foreground"
                        >
                            No service requests yet
                        </div>
                        <Button
                            as="a"
                            href="/shop/service-requests"
                            variant="outline"
                            class="w-full"
                        >
                            View All Requests
                        </Button>
                    </CardContent>
                </Card>

                <!-- Upcoming Bookings -->
                <Card>
                    <CardHeader>
                        <CardTitle>Upcoming Bookings</CardTitle>
                        <CardDescription>
                            Confirmed bookings in the next 7 days
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div
                            v-if="upcoming_bookings.length > 0"
                            class="space-y-3"
                        >
                            <div
                                v-for="booking in upcoming_bookings"
                                :key="booking.id"
                                class="flex flex-col gap-2 p-3 border rounded-lg hover:bg-accent/50 transition-colors"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-sm truncate">
                                            {{
                                                booking.service_request?.title ||
                                                'Service'
                                            }}
                                        </p>
                                        <p class="text-xs text-muted-foreground">
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
                                        </p>
                                    </div>
                                    <Badge
                                        :variant="getStatusColor(booking.status)"
                                        class="shrink-0"
                                    >
                                        {{ bookingStatusLabel(booking.status) }}
                                    </Badge>
                                </div>
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-semibold">
                                        {{ formatCurrency(booking.service_price) }}
                                    </p>
                                    <Button
                                        as="a"
                                        :href="`/shop/bookings/${booking.id}`"
                                        variant="ghost"
                                        size="sm"
                                    >
                                        View
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
                            href="/shop/bookings"
                            variant="outline"
                            class="w-full"
                        >
                            View All Bookings
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
