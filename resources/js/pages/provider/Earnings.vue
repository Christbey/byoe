<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { Booking, Payout, PaginatedResponse } from '@/types/marketplace';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import { Button } from '@/components/ui/button';

interface EarningsStats {
    periodEarnings: number;
    periodJobs: number;
    totalEarnings: number;
    totalJobs: number;
    averagePerJob: number;
}

interface PayoutStats {
    totalEarnings: number;
    pendingPayouts: number;
    thisMonthEarnings: number;
}

interface Props {
    tab: string;
    bookings: PaginatedResponse<Booking> | null;
    payouts: PaginatedResponse<Payout> | null;
    earningsStats: EarningsStats;
    payoutStats: PayoutStats;
    period: string;
    filter: string;
    stripeReady?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    stripeReady: false,
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Provider Dashboard', href: '/provider/dashboard' },
    { title: 'Earnings & Payouts', href: '/provider/earnings' },
];

const periods = [
    { key: 'week', label: 'This Week' },
    { key: 'month', label: 'This Month' },
    { key: 'quarter', label: 'This Quarter' },
    { key: 'year', label: 'This Year' },
    { key: 'all', label: 'All Time' },
];

const payoutFilters = [
    { key: 'all', label: 'All' },
    { key: 'pending', label: 'Pending' },
    { key: 'paid', label: 'Paid' },
    { key: 'failed', label: 'Failed' },
];

const switchTab = (t: string) => {
    router.get('/provider/earnings', { tab: t }, { preserveState: false });
};

const changePeriod = (p: string) => {
    router.get('/provider/earnings', { tab: 'earnings', period: p }, { preserveState: true, preserveScroll: true });
};

const changeFilter = (f: string) => {
    router.get('/provider/earnings', { tab: 'payouts', filter: f }, { preserveState: true, preserveScroll: true });
};

const loadMoreEarnings = () => {
    if (props.bookings && props.bookings.current_page < props.bookings.last_page) {
        router.get('/provider/earnings', { tab: 'earnings', period: props.period, page: props.bookings.current_page + 1 }, { preserveState: true, preserveScroll: true });
    }
};

const loadMorePayouts = () => {
    if (props.payouts && props.payouts.current_page < props.payouts.last_page) {
        router.get('/provider/earnings', { tab: 'payouts', filter: props.filter, page: props.payouts.current_page + 1 }, { preserveState: true, preserveScroll: true });
    }
};

const formatCurrency = (amount: number) =>
    new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);

const formatDate = (date: string | null | undefined) => {
    if (!date) return '—';
    return new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(date));
};

const payoutStatusVariant = (status: string) => {
    const map: Record<string, string> = {
        scheduled: 'warning',
        processing: 'info',
        paid: 'success',
        failed: 'destructive',
    };
    return map[status] ?? 'outline';
};
</script>

<template>
    <Head title="Earnings & Payouts" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
            <div class="space-y-2">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">Earnings & Payouts</h1>
                <p class="text-sm text-muted-foreground">Track your income and payout history</p>
            </div>

            <!-- Tab Navigation -->
                <div class="flex border-b">
                    <button
                        @click="switchTab('earnings')"
                        class="px-4 py-2.5 text-sm font-medium transition-colors border-b-2 -mb-px"
                        :class="tab === 'earnings' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
                    >
                        Earnings
                    </button>
                    <button
                        @click="switchTab('payouts')"
                        class="px-4 py-2.5 text-sm font-medium transition-colors border-b-2 -mb-px"
                        :class="tab === 'payouts' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
                    >
                        Payouts
                    </button>
                </div>

                <!-- ── Earnings Tab ────────────────────────────────────────────────── -->
                <template v-if="tab === 'earnings'">
                    <!-- Period Filter -->
                    <div class="flex gap-2 overflow-x-auto pb-1 -mx-4 px-4 md:mx-0 md:px-0">
                        <Badge
                            v-for="p in periods"
                            :key="p.key"
                            :variant="period === p.key ? 'default' : 'outline'"
                            as="button"
                            @click="changePeriod(p.key)"
                            class="cursor-pointer whitespace-nowrap px-4 py-2 text-sm font-medium transition-colors touch-manipulation"
                        >
                            {{ p.label }}
                        </Badge>
                    </div>

                    <!-- Stats Grid -->
                    <div class="grid gap-4 md:grid-cols-3">
                        <Card class="border-primary/30">
                            <CardHeader class="pb-2">
                                <CardDescription>{{ periods.find(p => p.key === period)?.label ?? 'Period' }} Earnings</CardDescription>
                                <CardTitle class="text-3xl text-primary">{{ formatCurrency(earningsStats.periodEarnings) }}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p class="text-sm text-muted-foreground">{{ earningsStats.periodJobs }} job{{ earningsStats.periodJobs !== 1 ? 's' : '' }} completed</p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader class="pb-2">
                                <CardDescription>All-Time Earnings</CardDescription>
                                <CardTitle class="text-3xl">{{ formatCurrency(earningsStats.totalEarnings) }}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p class="text-sm text-muted-foreground">{{ earningsStats.totalJobs }} total jobs</p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader class="pb-2">
                                <CardDescription>Average Per Job</CardDescription>
                                <CardTitle class="text-3xl">{{ formatCurrency(earningsStats.averagePerJob) }}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p class="text-sm text-muted-foreground">Per completed booking</p>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Job History Table -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Job History</CardTitle>
                        </CardHeader>
                        <CardContent class="p-0">
                            <div v-if="!bookings || bookings.data.length === 0" class="py-12 text-center text-muted-foreground">
                                No completed jobs in this period.
                            </div>

                            <template v-else>
                                <!-- Desktop Table -->
                                <table class="hidden md:table w-full text-sm">
                                    <thead class="border-b">
                                        <tr class="text-left text-muted-foreground">
                                            <th class="px-6 py-3 font-medium">Date Completed</th>
                                            <th class="px-6 py-3 font-medium">Shop</th>
                                            <th class="px-6 py-3 font-medium">Service</th>
                                            <th class="px-6 py-3 font-medium text-right">Payout</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        <tr v-for="booking in bookings.data" :key="booking.id" class="hover:bg-muted/30 transition-colors">
                                            <td class="px-6 py-4">{{ formatDate(booking.completed_at) }}</td>
                                            <td class="px-6 py-4">{{ booking.service_request?.shop_location?.shop?.name ?? '—' }}</td>
                                            <td class="px-6 py-4">{{ booking.service_request?.title ?? '—' }}</td>
                                            <td class="px-6 py-4 text-right font-semibold text-primary">{{ formatCurrency(booking.provider_payout) }}</td>
                                        </tr>
                                    </tbody>
                                </table>

                                <!-- Mobile Cards -->
                                <div class="md:hidden divide-y">
                                    <div v-for="booking in bookings.data" :key="booking.id" class="p-4 space-y-1">
                                        <div class="flex items-center justify-between">
                                            <span class="font-semibold">{{ formatCurrency(booking.provider_payout) }}</span>
                                            <span class="text-xs text-muted-foreground">{{ formatDate(booking.completed_at) }}</span>
                                        </div>
                                        <p class="text-sm font-medium">{{ booking.service_request?.shop_location?.shop?.name ?? '—' }}</p>
                                        <p class="text-xs text-muted-foreground">{{ booking.service_request?.title ?? '—' }}</p>
                                    </div>
                                </div>
                            </template>
                        </CardContent>
                    </Card>

                    <div v-if="bookings && bookings.current_page < bookings.last_page" class="flex justify-center">
                        <Button variant="outline" @click="loadMoreEarnings">Load More</Button>
                    </div>
                </template>

                <!-- ── Payouts Tab ─────────────────────────────────────────────────── -->
                <template v-else-if="tab === 'payouts'">
                    <!-- Stripe Not Ready Warning -->
                    <div v-if="!stripeReady" class="rounded-lg border border-amber-300 bg-amber-50 dark:bg-amber-950/20 p-4">
                        <p class="text-sm font-medium text-amber-800 dark:text-amber-300">Stripe payout account not fully set up. Payouts may be delayed.</p>
                        <a href="/provider/stripe-setup" class="text-sm text-amber-700 dark:text-amber-400 underline mt-1 inline-block">Complete Stripe setup →</a>
                    </div>

                    <!-- Payout Stats -->
                    <div class="grid gap-4 md:grid-cols-3">
                        <Card class="border-primary/30">
                            <CardHeader class="pb-2">
                                <CardDescription>Total Paid Out</CardDescription>
                                <CardTitle class="text-3xl text-primary">{{ formatCurrency(payoutStats.totalEarnings) }}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p class="text-sm text-muted-foreground">All-time paid payouts</p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader class="pb-2">
                                <CardDescription>Pending Payouts</CardDescription>
                                <CardTitle class="text-3xl">{{ formatCurrency(payoutStats.pendingPayouts) }}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p class="text-sm text-muted-foreground">Awaiting transfer</p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader class="pb-2">
                                <CardDescription>This Month</CardDescription>
                                <CardTitle class="text-3xl">{{ formatCurrency(payoutStats.thisMonthEarnings) }}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p class="text-sm text-muted-foreground">Paid out this month</p>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Status Filter -->
                    <div class="flex gap-2 overflow-x-auto pb-1 -mx-4 px-4 md:mx-0 md:px-0">
                        <Badge
                            v-for="f in payoutFilters"
                            :key="f.key"
                            :variant="filter === f.key ? 'default' : 'outline'"
                            as="button"
                            @click="changeFilter(f.key)"
                            class="cursor-pointer whitespace-nowrap px-4 py-2 text-sm font-medium transition-colors touch-manipulation"
                        >
                            {{ f.label }}
                        </Badge>
                    </div>

                    <!-- Payouts List -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Payout History</CardTitle>
                        </CardHeader>
                        <CardContent class="p-0">
                            <div v-if="!payouts || payouts.data.length === 0" class="py-12 text-center text-muted-foreground">
                                No payouts found.
                            </div>

                            <template v-else>
                                <!-- Desktop Table -->
                                <table class="hidden md:table w-full text-sm">
                                    <thead class="border-b">
                                        <tr class="text-left text-muted-foreground">
                                            <th class="px-6 py-3 font-medium">Date</th>
                                            <th class="px-6 py-3 font-medium">Shop</th>
                                            <th class="px-6 py-3 font-medium">Service</th>
                                            <th class="px-6 py-3 font-medium">Status</th>
                                            <th class="px-6 py-3 font-medium text-right">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        <tr v-for="payout in payouts.data" :key="payout.id" class="hover:bg-muted/30 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap">{{ formatDate(payout.paid_at ?? payout.created_at) }}</td>
                                            <td class="px-6 py-4">{{ payout.booking?.service_request?.shop_location?.shop?.name ?? '—' }}</td>
                                            <td class="px-6 py-4">{{ payout.booking?.service_request?.title ?? '—' }}</td>
                                            <td class="px-6 py-4">
                                                <Badge :variant="payoutStatusVariant(payout.status)">{{ payout.status }}</Badge>
                                            </td>
                                            <td class="px-6 py-4 text-right font-semibold text-primary">{{ formatCurrency(payout.amount) }}</td>
                                        </tr>
                                    </tbody>
                                </table>

                                <!-- Mobile Cards -->
                                <div class="md:hidden divide-y">
                                    <div v-for="payout in payouts.data" :key="payout.id" class="p-4 space-y-2">
                                        <div class="flex items-center justify-between">
                                            <span class="font-semibold">{{ formatCurrency(payout.amount) }}</span>
                                            <Badge :variant="payoutStatusVariant(payout.status)" class="capitalize">{{ payout.status }}</Badge>
                                        </div>
                                        <p class="text-sm font-medium">{{ payout.booking?.service_request?.shop_location?.shop?.name ?? '—' }}</p>
                                        <p class="text-xs text-muted-foreground">{{ payout.booking?.service_request?.title ?? '—' }}</p>
                                        <p class="text-xs text-muted-foreground">{{ formatDate(payout.paid_at ?? payout.created_at) }}</p>
                                    </div>
                                </div>
                            </template>
                        </CardContent>
                    </Card>

                    <div v-if="payouts && payouts.current_page < payouts.last_page" class="flex justify-center">
                        <Button variant="outline" @click="loadMorePayouts">Load More</Button>
                    </div>
                </template>
        </div>
    </AppLayout>
</template>
