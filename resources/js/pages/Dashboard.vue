<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import type { Booking, ServiceRequest } from '@/types/marketplace';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';
import { dashboard } from '@/routes';

// ─── Shared types ────────────────────────────────────────────────────────────

interface ProviderStats {
    earnings_this_month: number;
    earnings_this_week: number;
    total_earnings: number;
    upcoming_bookings: number;
    completed_bookings: number;
    average_rating: number;
}

interface ShopStats {
    active_requests: number;
    upcoming_bookings: number;
    total_spent: number;
}

interface Props {
    view: 'provider' | 'shop' | 'default';
    // Provider props
    stats?: ProviderStats | ShopStats;
    upcoming_bookings?: Booking[];
    recent_activity?: Booking[];
    // Shop props
    recent_requests?: ServiceRequest[];
}

const props = withDefaults(defineProps<Props>(), {
    upcoming_bookings: () => [],
    recent_activity: () => [],
    recent_requests: () => [],
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
];

// ─── Helpers ─────────────────────────────────────────────────────────────────

const formatCurrency = (amount: number) =>
    new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);

const formatDate = (date: string) =>
    date ? new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(date)) : '—';

const formatTime = (time: string) => {
    if (!time) return '';
    return new Date(time).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
};


const providerStats = () => props.stats as ProviderStats;
const shopStats = () => props.stats as ShopStats;
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">

            <!-- ═══════════════════════════════════ PROVIDER VIEW ═══════ -->
            <template v-if="view === 'provider'">
                <div class="space-y-2">
                    <h1 class="text-2xl font-bold tracking-tight md:text-3xl">Provider Dashboard</h1>
                    <p class="text-sm text-muted-foreground">Track your earnings and upcoming bookings</p>
                </div>

                <!-- Earnings Row -->
                    <div class="grid gap-4 md:grid-cols-3">
                        <Card class="border-primary/30">
                            <CardHeader class="pb-2">
                                <CardDescription>This Week</CardDescription>
                                <CardTitle class="text-3xl text-primary">
                                    {{ formatCurrency(providerStats().earnings_this_week) }}
                                </CardTitle>
                            </CardHeader>
                        </Card>
                        <Card>
                            <CardHeader class="pb-2">
                                <CardDescription>This Month</CardDescription>
                                <CardTitle class="text-3xl">
                                    {{ formatCurrency(providerStats().earnings_this_month) }}
                                </CardTitle>
                            </CardHeader>
                        </Card>
                        <Card>
                            <CardHeader class="pb-2">
                                <CardDescription>All Time</CardDescription>
                                <CardTitle class="text-3xl">
                                    {{ formatCurrency(providerStats().total_earnings) }}
                                </CardTitle>
                            </CardHeader>
                        </Card>
                    </div>

                    <!-- Quick Stats Row -->
                    <div class="grid gap-4 md:grid-cols-3">
                        <Card>
                            <CardHeader class="pb-2">
                                <CardDescription>Upcoming Jobs</CardDescription>
                                <CardTitle class="text-2xl">{{ providerStats().upcoming_bookings }}</CardTitle>
                            </CardHeader>
                        </Card>
                        <Card>
                            <CardHeader class="pb-2">
                                <CardDescription>Completed Jobs</CardDescription>
                                <CardTitle class="text-2xl">{{ providerStats().completed_bookings }}</CardTitle>
                            </CardHeader>
                        </Card>
                        <Card>
                            <CardHeader class="pb-2">
                                <CardDescription>Average Rating</CardDescription>
                                <CardTitle class="text-2xl">
                                    {{ Number(providerStats().average_rating).toFixed(1) }} ★
                                </CardTitle>
                            </CardHeader>
                        </Card>
                    </div>

                    <!-- Quick Actions -->
                    <div class="flex flex-wrap gap-3">
                        <Button as="a" href="/provider/available-requests">Browse Available Requests</Button>
                        <Button as="a" href="/provider/bookings" variant="outline">My Bookings</Button>
                        <Button as="a" href="/provider/earnings" variant="outline">Earnings Detail</Button>
                    </div>

                    <!-- Upcoming Bookings -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Upcoming Bookings</CardTitle>
                            <CardDescription>Your next 14 days</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="upcoming_bookings.length > 0" class="divide-y">
                                <div
                                    v-for="booking in upcoming_bookings"
                                    :key="booking.id"
                                    class="flex items-start justify-between gap-4 py-4 first:pt-0 last:pb-0"
                                >
                                    <div class="flex-1 min-w-0 space-y-0.5">
                                        <p class="font-semibold truncate">
                                            {{ booking.service_request?.title || 'Service' }}
                                        </p>
                                        <p class="text-sm text-muted-foreground">
                                            {{ formatDate(booking.service_request?.service_date || '') }}
                                            · {{ formatTime(booking.service_request?.start_time || '') }}
                                        </p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ booking.service_request?.shop_location?.shop?.name }}
                                            · {{ booking.service_request?.shop_location?.city }}
                                        </p>
                                    </div>
                                    <div class="flex flex-col items-end gap-1 shrink-0">
                                        <span class="font-bold text-primary">{{ formatCurrency(booking.provider_payout) }}</span>
                                        <Link :href="`/provider/bookings/${booking.id}`">
                                            <Button variant="ghost" size="sm">View</Button>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="py-8 text-center text-sm text-muted-foreground">No upcoming bookings</p>
                            <div class="pt-4 border-t mt-4">
                                <Button as="a" href="/provider/bookings" variant="outline" class="w-full">
                                    View All Bookings
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
            </template>

            <!-- ════════════════════════════════════════ SHOP VIEW ═══════ -->
            <template v-else-if="view === 'shop'">
                <div class="space-y-2">
                    <h1 class="text-2xl font-bold tracking-tight md:text-3xl">Shop Dashboard</h1>
                    <p class="text-sm text-muted-foreground">Overview of your service requests and bookings</p>
                </div>

                <!-- Stats -->
                <div class="grid gap-4 md:grid-cols-3">
                    <Card class="border-primary/30">
                        <CardHeader class="pb-2">
                            <CardDescription>Active Requests</CardDescription>
                            <CardTitle class="text-3xl text-primary">{{ shopStats().active_requests }}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-xs text-muted-foreground">Open service requests</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader class="pb-2">
                            <CardDescription>Upcoming Bookings</CardDescription>
                            <CardTitle class="text-3xl">{{ shopStats().upcoming_bookings }}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-xs text-muted-foreground">Next 7 days</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader class="pb-2">
                            <CardDescription>Total Spent</CardDescription>
                            <CardTitle class="text-3xl">{{ formatCurrency(shopStats().total_spent) }}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-xs text-muted-foreground">All time</p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Quick Actions -->
                <div class="flex flex-wrap gap-3">
                    <Button as="a" href="/shop/service-requests/create">+ Create Request</Button>
                    <Button as="a" href="/shop/service-requests" variant="outline">All Requests</Button>
                    <Button as="a" href="/shop/bookings" variant="outline">Bookings</Button>
                </div>

                <!-- Two columns -->
                <div class="grid gap-4 lg:grid-cols-2">
                    <!-- Recent Requests -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Recent Service Requests</CardTitle>
                            <CardDescription>Your last 5 requests</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="recent_requests.length > 0" class="divide-y">
                                <div
                                    v-for="request in recent_requests"
                                    :key="request.id"
                                    class="flex items-start justify-between gap-3 py-3 first:pt-0 last:pb-0"
                                >
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-sm truncate">{{ request.title }}</p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ formatDate(request.service_date) }}
                                            · {{ formatTime(request.start_time) }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <Badge :variant="request.status_variant">{{ request.status_label }}</Badge>
                                        <Link :href="`/shop/service-requests/${request.id}`">
                                            <Button variant="ghost" size="sm">View</Button>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="py-8 text-center text-sm text-muted-foreground">No service requests yet</p>
                            <div class="pt-4 border-t mt-4">
                                <Button as="a" href="/shop/service-requests" variant="outline" class="w-full">
                                    View All Requests
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Upcoming Bookings -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Upcoming Bookings</CardTitle>
                            <CardDescription>Next 7 days</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="upcoming_bookings.length > 0" class="divide-y">
                                <div
                                    v-for="booking in upcoming_bookings"
                                    :key="booking.id"
                                    class="flex items-start justify-between gap-3 py-3 first:pt-0 last:pb-0"
                                >
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-sm truncate">
                                            {{ booking.service_request?.title || 'Service' }}
                                        </p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ formatDate(booking.service_request?.service_date || '') }}
                                        </p>
                                        <p v-if="booking.provider" class="text-xs text-muted-foreground">
                                            Provider: {{ booking.provider.user?.name ?? '—' }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <Badge :variant="booking.status_variant">{{ booking.status_label }}</Badge>
                                        <Link :href="`/shop/bookings/${booking.id}`">
                                            <Button variant="ghost" size="sm">View</Button>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="py-8 text-center text-sm text-muted-foreground">No upcoming bookings</p>
                            <div class="pt-4 border-t mt-4">
                                <Button as="a" href="/shop/bookings" variant="outline" class="w-full">
                                    View All Bookings
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </template>

            <!-- ══════════════════════════════════════ DEFAULT VIEW ═══════ -->
            <template v-else>
                <div class="space-y-2">
                    <h1 class="text-2xl font-bold tracking-tight md:text-3xl">Dashboard</h1>
                    <p class="text-sm text-muted-foreground">Welcome! Your account is set up and ready.</p>
                </div>
            </template>

        </div>
    </AppLayout>
</template>
