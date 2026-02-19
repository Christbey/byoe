<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { PaginatedResponse } from '@/types/marketplace';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';

interface Payout {
    id: number;
    booking_id: number;
    amount: number;
    status: 'scheduled' | 'processing' | 'paid' | 'failed' | 'cancelled';
    scheduled_for?: string;
    paid_at?: string;
    stripe_transfer_id?: string;
    created_at: string;
    booking?: {
        id: number;
        service_request?: {
            title: string;
        };
    };
}

interface PayoutStats {
    totalEarnings: number;
    pendingPayouts: number;
    thisMonthEarnings: number;
}

interface Props {
    payouts: PaginatedResponse<Payout>;
    stats: PayoutStats;
    filter: string;
    stripeReady?: boolean;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Payouts',
        href: '/provider/payouts',
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

const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
        scheduled: 'secondary',
        processing: 'default',
        paid: 'default',
        failed: 'destructive',
    };
    return colors[status] || 'outline';
};
</script>

<template>
    <Head title="Payouts" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
            <!-- Header -->
            <div class="space-y-2">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                    Payout History
                </h1>
                <p class="text-sm text-muted-foreground md:text-base">
                    Track your earnings and payout schedule
                </p>
            </div>

            <!-- Stripe setup banner -->
            <div
                v-if="!stripeReady"
                class="flex flex-col gap-3 rounded-lg border border-amber-300 bg-amber-50 p-4 dark:bg-amber-950/20 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <p class="text-sm font-medium text-amber-800 dark:text-amber-300">
                        Payout account not set up
                    </p>
                    <p class="text-sm text-amber-700 dark:text-amber-400">
                        Complete your Stripe setup to receive payouts for completed bookings.
                    </p>
                </div>
                <Button as="a" href="/provider/stripe-setup" size="sm" class="shrink-0">
                    Set up payouts
                </Button>
            </div>

            <!-- Total Earnings Summary -->
            <Card>
                <CardHeader>
                    <CardDescription>Total Earnings</CardDescription>
                    <CardTitle class="text-4xl">
                        {{ formatCurrency(stats.totalEarnings) }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-sm text-muted-foreground">
                        Total amount earned from all completed bookings
                    </p>
                </CardContent>
            </Card>

            <!-- Payouts Table (Desktop) -->
            <Card class="hidden md:block">
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="border-b bg-muted/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                        Scheduled Date
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                        Amount
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                        Paid Date
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                        Booking
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr
                                    v-for="payout in payouts.data"
                                    :key="payout.id"
                                    class="hover:bg-muted/50 transition-colors"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">
                                        {{ formatDate(payout.scheduled_for) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold">
                                        {{ formatCurrency(payout.amount) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <Badge :variant="getStatusColor(payout.status)">
                                            {{ payout.status }}
                                        </Badge>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">
                                        {{
                                            payout.paid_at
                                                ? formatDate(payout.paid_at)
                                                : '-'
                                        }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <a
                                            :href="`/provider/bookings/${payout.booking_id}`"
                                            class="hover:underline text-primary"
                                        >
                                            #{{ payout.booking_id }}
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <!-- Payouts List (Mobile) -->
            <div v-if="payouts.data.length > 0" class="space-y-4 md:hidden">
                <Card v-for="payout in payouts.data" :key="payout.id">
                    <CardContent class="pt-6 space-y-3">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-xl">
                                    {{ formatCurrency(payout.amount) }}
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    {{
                                        payout.booking?.service_request?.title ||
                                        'Service Payment'
                                    }}
                                </p>
                            </div>
                            <Badge :variant="getStatusColor(payout.status)">
                                {{ payout.status }}
                            </Badge>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-sm pt-2 border-t">
                            <div>
                                <p class="text-muted-foreground">Scheduled</p>
                                <p class="font-medium">
                                    {{ formatDate(payout.scheduled_for) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-muted-foreground">Paid</p>
                                <p class="font-medium">
                                    {{
                                        payout.paid_at
                                            ? formatDate(payout.paid_at)
                                            : '-'
                                    }}
                                </p>
                            </div>
                        </div>
                        <div class="pt-2">
                            <Button
                                as="a"
                                :href="`/provider/bookings/${payout.booking_id}`"
                                variant="outline"
                                size="sm"
                                class="w-full"
                            >
                                View Booking #{{ payout.booking_id }}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <Card v-if="payouts.data.length === 0">
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
                            d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"
                        />
                    </svg>
                    <h3 class="text-lg font-semibold mb-2">No payouts yet</h3>
                    <p class="text-sm text-muted-foreground max-w-md">
                        Complete bookings to start earning. Payouts are processed
                        after you complete a service.
                    </p>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div
                v-if="payouts.data.length > 0"
                class="text-center text-sm text-muted-foreground pb-4"
            >
                Page {{ payouts.current_page }} of {{ payouts.last_page }}
            </div>
        </div>
    </AppLayout>
</template>
