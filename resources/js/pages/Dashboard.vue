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
import PageHelp from '@/components/marketplace/PageHelp.vue';

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
    stats?: ProviderStats | ShopStats;
    upcoming_bookings?: Booking[];
    available_requests?: ServiceRequest[];
    recent_activity?: Booking[];
    recent_requests?: ServiceRequest[];
}

const props = withDefaults(defineProps<Props>(), {
    upcoming_bookings: () => [],
    available_requests: () => [],
    recent_activity: () => [],
    recent_requests: () => [],
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
];

const formatCurrency = (amount: number) =>
    new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);

const formatDate = (date: string) =>
    date ? new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(date)) : '—';

const formatTime = (time: string) => {
    if (!time) return '';

    const [hours, minutes] = time.split(':').map(Number);
    const period = hours >= 12 ? 'PM' : 'AM';
    const displayHours = hours % 12 || 12;

    return `${displayHours}:${minutes.toString().padStart(2, '0')} ${period}`;
};

const providerStats = () => props.stats as ProviderStats;
const shopStats = () => props.stats as ShopStats;
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-5">
            <template v-if="view === 'provider'">
                <section class="ios-surface overflow-hidden px-5 py-6 md:px-7">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                        <div class="space-y-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-muted-foreground">
                                Provider dashboard
                            </p>
                            <div>
                                <h1 class="text-3xl font-semibold tracking-[-0.05em] md:text-4xl">
                                    Your schedule, payouts, and momentum.
                                </h1>
                                <p class="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground md:text-base">
                                    See open requests, monitor upcoming work, and keep earnings visible without digging through menus.
                                </p>
                            </div>
                            <PageHelp
                                storage-key="root-provider-dashboard"
                                :steps="[
                                    'This dashboard is your operating home base: browse requests, track bookings, and monitor earnings.',
                                    'A request becomes a booking after you accept it and the booking is confirmed.',
                                    'Completed shifts flow into ratings and payouts, so the booking page stays the source of truth.',
                                    'If anything feels unclear, use the booking details page rather than guessing from status labels alone.',
                                ]"
                            />
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3 lg:min-w-[26rem]">
                            <div class="ios-panel px-4 py-4">
                                <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">This week</p>
                                <p class="mt-2 text-2xl font-semibold tracking-[-0.04em] text-primary">
                                    {{ formatCurrency(providerStats().earnings_this_week) }}
                                </p>
                            </div>
                            <div class="ios-panel px-4 py-4">
                                <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Upcoming</p>
                                <p class="mt-2 text-2xl font-semibold tracking-[-0.04em]">
                                    {{ providerStats().upcoming_bookings }}
                                </p>
                            </div>
                            <div class="ios-panel px-4 py-4">
                                <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Rating</p>
                                <p class="mt-2 text-2xl font-semibold tracking-[-0.04em]">
                                    {{ Number(providerStats().average_rating).toFixed(1) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="flex flex-wrap gap-3">
                    <Button as="a" href="/provider/available-requests">Browse requests</Button>
                    <Button as="a" href="/provider/bookings" variant="outline">My bookings</Button>
                    <Button as="a" href="/provider/earnings" variant="outline">Payout history</Button>
                </section>

                <section class="grid gap-5 xl:grid-cols-[1.15fr_0.85fr]">
                    <Card>
                        <CardHeader class="px-6 pb-0">
                            <CardTitle class="text-2xl tracking-[-0.04em]">Available requests</CardTitle>
                            <CardDescription>Recent work you can accept right now</CardDescription>
                        </CardHeader>
                        <CardContent class="px-6">
                            <div v-if="available_requests.length > 0" class="space-y-3">
                                <div
                                    v-for="request in available_requests"
                                    :key="request.id"
                                    class="rounded-[22px] border border-white/50 bg-white/62 p-4 backdrop-blur-md dark:border-white/8 dark:bg-white/6"
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-base font-semibold tracking-[-0.02em]">
                                                {{ request.title }}
                                            </p>
                                            <p class="mt-1 text-sm text-muted-foreground">
                                                {{ formatDate(request.service_date) }}
                                                <template v-if="request.start_time">
                                                    · {{ formatTime(request.start_time) }} - {{ formatTime(request.end_time) }}
                                                </template>
                                            </p>
                                            <p class="mt-1 text-xs text-muted-foreground">
                                                {{ request.shop_location?.shop?.name }}
                                                <template v-if="request.shop_location?.city">
                                                    · {{ request.shop_location?.city }}, {{ request.shop_location?.state }}
                                                </template>
                                            </p>
                                        </div>
                                        <div class="shrink-0 text-right">
                                            <p class="text-lg font-semibold tracking-[-0.03em] text-primary">
                                                {{ formatCurrency(request.price) }}
                                            </p>
                                            <Link href="/provider/available-requests">
                                                <Button variant="ghost" size="sm" class="mt-2">Open</Button>
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="py-10 text-center text-sm text-muted-foreground">
                                No available requests right now.
                            </p>
                        </CardContent>
                    </Card>

                    <div class="grid gap-5">
                        <Card>
                            <CardHeader class="px-6 pb-0">
                                <CardTitle class="text-2xl tracking-[-0.04em]">Performance</CardTitle>
                                <CardDescription>Progress at a glance</CardDescription>
                            </CardHeader>
                            <CardContent class="grid gap-3 px-6 sm:grid-cols-2">
                                <div class="ios-panel px-4 py-4">
                                    <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">This month</p>
                                    <p class="mt-2 text-xl font-semibold tracking-[-0.03em]">
                                        {{ formatCurrency(providerStats().earnings_this_month) }}
                                    </p>
                                </div>
                                <div class="ios-panel px-4 py-4">
                                    <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">All time</p>
                                    <p class="mt-2 text-xl font-semibold tracking-[-0.03em]">
                                        {{ formatCurrency(providerStats().total_earnings) }}
                                    </p>
                                </div>
                                <div class="ios-panel px-4 py-4">
                                    <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Completed</p>
                                    <p class="mt-2 text-xl font-semibold tracking-[-0.03em]">
                                        {{ providerStats().completed_bookings }}
                                    </p>
                                </div>
                                <div class="ios-panel px-4 py-4">
                                    <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Status</p>
                                    <p class="mt-2 text-xl font-semibold tracking-[-0.03em]">
                                        Ready to accept
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </section>
            </template>

            <template v-else-if="view === 'shop'">
                <section class="ios-surface overflow-hidden px-5 py-6 md:px-7">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                        <div class="space-y-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-muted-foreground">
                                Shop dashboard
                            </p>
                            <div>
                                <h1 class="text-3xl font-semibold tracking-[-0.05em] md:text-4xl">
                                    Requests, bookings, and spend in one clean view.
                                </h1>
                                <p class="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground md:text-base">
                                    Post work quickly, watch coverage progress, and keep active operations readable at a glance.
                                </p>
                            </div>
                            <PageHelp
                                storage-key="root-shop-dashboard"
                                :steps="[
                                    'Create a service request when you need shift coverage at a specific location and time.',
                                    'When a provider accepts it, the request becomes a booking with clearer status tracking.',
                                    'Use the booking page to confirm completion, rate the provider, or open a dispute if needed.',
                                    'Think of this dashboard as your live board for open work, confirmed coverage, and spend.',
                                ]"
                            />
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3 lg:min-w-[26rem]">
                            <div class="ios-panel px-4 py-4">
                                <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Active requests</p>
                                <p class="mt-2 text-2xl font-semibold tracking-[-0.04em] text-primary">
                                    {{ shopStats().active_requests }}
                                </p>
                            </div>
                            <div class="ios-panel px-4 py-4">
                                <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Upcoming</p>
                                <p class="mt-2 text-2xl font-semibold tracking-[-0.04em]">
                                    {{ shopStats().upcoming_bookings }}
                                </p>
                            </div>
                            <div class="ios-panel px-4 py-4">
                                <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Total spend</p>
                                <p class="mt-2 text-2xl font-semibold tracking-[-0.04em]">
                                    {{ formatCurrency(shopStats().total_spent) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="flex flex-wrap gap-3">
                    <Button as="a" href="/shop/service-requests/create">Create request</Button>
                    <Button as="a" href="/shop/service-requests" variant="outline">All requests</Button>
                    <Button as="a" href="/shop/bookings" variant="outline">Bookings</Button>
                </section>

                <section class="grid gap-5 lg:grid-cols-2">
                    <Card>
                        <CardHeader class="px-6 pb-0">
                            <CardTitle class="text-2xl tracking-[-0.04em]">Recent service requests</CardTitle>
                            <CardDescription>Your latest posted work</CardDescription>
                        </CardHeader>
                        <CardContent class="px-6">
                            <div v-if="recent_requests.length > 0" class="space-y-3">
                                <div
                                    v-for="request in recent_requests"
                                    :key="request.id"
                                    class="rounded-[22px] border border-white/50 bg-white/62 p-4 backdrop-blur-md dark:border-white/8 dark:bg-white/6"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-base font-semibold tracking-[-0.02em]">{{ request.title }}</p>
                                            <p class="mt-1 text-sm text-muted-foreground">
                                                {{ formatDate(request.service_date) }}
                                                <template v-if="request.start_time">
                                                    · {{ formatTime(request.start_time) }}
                                                </template>
                                            </p>
                                        </div>
                                        <div class="shrink-0 text-right">
                                            <Badge :variant="request.status_variant">{{ request.status_label }}</Badge>
                                            <div class="mt-2">
                                                <Link :href="`/shop/service-requests/${request.id}`">
                                                    <Button variant="ghost" size="sm">Open</Button>
                                                </Link>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="py-10 text-center text-sm text-muted-foreground">
                                No service requests yet.
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="px-6 pb-0">
                            <CardTitle class="text-2xl tracking-[-0.04em]">Upcoming bookings</CardTitle>
                            <CardDescription>Next scheduled coverage</CardDescription>
                        </CardHeader>
                        <CardContent class="px-6">
                            <div v-if="upcoming_bookings.length > 0" class="space-y-3">
                                <div
                                    v-for="booking in upcoming_bookings"
                                    :key="booking.id"
                                    class="rounded-[22px] border border-white/50 bg-white/62 p-4 backdrop-blur-md dark:border-white/8 dark:bg-white/6"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-base font-semibold tracking-[-0.02em]">
                                                {{ booking.service_request?.title || 'Service booking' }}
                                            </p>
                                            <p class="mt-1 text-sm text-muted-foreground">
                                                {{ formatDate(booking.service_request?.service_date || '') }}
                                            </p>
                                            <p v-if="booking.provider" class="mt-1 text-xs text-muted-foreground">
                                                Provider: {{ booking.provider.user?.name ?? '—' }}
                                            </p>
                                        </div>
                                        <div class="shrink-0 text-right">
                                            <Badge :variant="booking.status_variant">{{ booking.status_label }}</Badge>
                                            <div class="mt-2">
                                                <Link :href="`/shop/bookings/${booking.id}`">
                                                    <Button variant="ghost" size="sm">Open</Button>
                                                </Link>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="py-10 text-center text-sm text-muted-foreground">
                                No upcoming bookings.
                            </p>
                        </CardContent>
                    </Card>
                </section>
            </template>

            <template v-else>
                <section class="ios-surface px-6 py-8 md:px-8">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-muted-foreground">
                        Dashboard
                    </p>
                    <h1 class="mt-3 text-3xl font-semibold tracking-[-0.05em] md:text-4xl">
                        Your account is ready.
                    </h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground md:text-base">
                        Complete your profile to unlock the full marketplace workflow and role-specific tools.
                    </p>
                </section>
            </template>
        </div>
    </AppLayout>
</template>
