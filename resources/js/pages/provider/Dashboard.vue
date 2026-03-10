<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { ServiceRequest } from '@/types/marketplace';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import PageHeader from '@/components/ui/page-header/PageHeader.vue';
import PageHelp from '@/components/marketplace/PageHelp.vue';
import StatCard from '@/components/marketplace/StatCard.vue';
import ListItem from '@/components/marketplace/ListItem.vue';
import EmptyState from '@/components/ui/empty-state/EmptyState.vue';
import Icon from '@/components/ui/icon/Icon.vue';

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
    provider: {
        id: number;
        trust_score: number;
        reliability_score: number;
        vetting_status: string;
        trust_tier: string;
        trust_action_items?: Array<{
            title: string;
            detail: string;
            action_label: string;
            action_href: string;
            severity: 'warning' | 'danger' | 'info';
        }>;
    };
    earnings: EarningsStats;
    stats: ProviderStats;
    available_requests: ServiceRequest[];
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
    if (!time) return '';

    // Handle plain time strings like "08:00:00" or "08:00"
    const [hours, minutes] = time.split(':').map(Number);
    const period = hours >= 12 ? 'PM' : 'AM';
    const displayHours = hours % 12 || 12;

    return `${displayHours}:${minutes.toString().padStart(2, '0')} ${period}`;
};
</script>

<template>
    <Head title="Provider Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
            <!-- Page Header -->
            <PageHeader
                title="Provider Dashboard"
                description="Track your earnings, upcoming bookings, and performance metrics"
            >
                <template #help>
                    <PageHelp
                        storage-key="provider-dashboard"
                        :steps="[
                            'Browse available service requests from coffee shops in the marketplace.',
                            'Accept a request to create a booking — show up, do great work, get paid.',
                            'Your earnings are paid out after the shop marks the booking complete.',
                            'Keep your profile complete and ratings high to appear more attractive to shops.',
                        ]"
                    />
                </template>
            </PageHeader>

            <!-- Earnings Summary -->
            <div class="grid gap-4 md:grid-cols-3">
                <StatCard
                    icon="TrendingUp"
                    label="This Week"
                    :value="formatCurrency(earnings.this_week)"
                    subtitle="Earnings from this week"
                    variant="success"
                />

                <StatCard
                    icon="Calendar"
                    label="This Month"
                    :value="formatCurrency(earnings.this_month)"
                    subtitle="Monthly total"
                    variant="accent"
                />

                <StatCard
                    icon="Wallet"
                    label="All Time"
                    :value="formatCurrency(earnings.all_time)"
                    subtitle="Lifetime earnings"
                />
            </div>

            <!-- Quick Stats -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard
                    icon="CheckCircle2"
                    label="Completed"
                    :value="stats.completed_jobs"
                    subtitle="Total jobs"
                    size="sm"
                />

                <StatCard
                    icon="Star"
                    label="Rating"
                    :value="`${Number(stats.average_rating).toFixed(1)} ⭐`"
                    subtitle="Average rating"
                    size="sm"
                />

                <StatCard
                    icon="DollarSign"
                    label="Earnings"
                    :value="formatCurrency(stats.total_earnings)"
                    subtitle="Total earned"
                    size="sm"
                />

                <StatCard
                    icon="Mail"
                    label="Invitations"
                    :value="stats.pending_invitations"
                    subtitle="Pending"
                    size="sm"
                    :variant="stats.pending_invitations > 0 ? 'warning' : 'default'"
                />
            </div>

            <Card v-if="provider.trust_action_items?.length" class="border-amber-200/70 bg-amber-50/80">
                <CardHeader>
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <CardTitle>Trust Tasks</CardTitle>
                            <CardDescription>
                                Trust {{ provider.trust_score }} · Reliability {{ provider.reliability_score }} ·
                                {{ provider.vetting_status.replace('_', ' ') }}
                            </CardDescription>
                        </div>
                        <Badge :variant="provider.trust_tier === 'at_risk' ? 'destructive' : 'outline'">
                            {{ provider.trust_tier.replace('_', ' ') }}
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent class="grid gap-3 md:grid-cols-2">
                    <div
                        v-for="item in provider.trust_action_items"
                        :key="item.title"
                        class="rounded-2xl border border-white/60 bg-white/80 p-4 shadow-sm"
                    >
                        <p class="text-sm font-semibold">{{ item.title }}</p>
                        <p class="mt-1 text-sm text-muted-foreground">{{ item.detail }}</p>
                        <Button :href="item.action_href" variant="outline" size="sm" class="mt-3">
                            {{ item.action_label }}
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Quick Actions -->
            <div class="flex flex-col sm:flex-row gap-3">
                <Button href="/provider/available-requests" class="w-full sm:w-auto">
                    <Icon name="Search" size="sm" class="mr-2" />
                    View Available Requests
                </Button>
                <Button
                    href="/provider/bookings"
                    variant="outline"
                    class="w-full sm:w-auto"
                >
                    <Icon name="Calendar" size="sm" class="mr-2" />
                    My Bookings
                </Button>
                <Button
                    href="/provider/earnings"
                    variant="outline"
                    class="w-full sm:w-auto"
                >
                    <Icon name="BarChart3" size="sm" class="mr-2" />
                    Earnings Detail
                </Button>
            </div>

            <!-- Available Service Requests -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>Available Requests</CardTitle>
                            <CardDescription>
                                Recent service requests you can accept
                            </CardDescription>
                        </div>
                        <Icon name="FileText" class="text-muted-foreground" />
                    </div>
                </CardHeader>
                <CardContent class="space-y-3">
                    <template v-if="available_requests.length > 0">
                        <ListItem
                            v-for="request in available_requests"
                            :key="request.id"
                            status="success"
                            clickable
                            @click="router.visit(`/provider/available-requests`)"
                        >
                            <template #header>
                                <h3 class="font-semibold text-sm">
                                    {{ request.title }}
                                </h3>
                            </template>

                            <template #content>
                                <div class="flex flex-col gap-1">
                                    <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                        <Icon name="Calendar" size="xs" />
                                        <span>{{ formatDate(request.service_date) }}</span>
                                        <span>•</span>
                                        <Icon name="Clock" size="xs" />
                                        <span>
                                            {{ formatTime(request.start_time) }}
                                            -
                                            {{ formatTime(request.end_time) }}
                                        </span>
                                    </div>
                                    <div
                                        v-if="request.shop_location"
                                        class="flex items-center gap-1.5 text-xs text-muted-foreground"
                                    >
                                        <Icon name="Store" size="xs" />
                                        <span>{{ request.shop_location.shop?.name }}</span>
                                    </div>
                                </div>
                            </template>

                            <template #footer>
                                <div
                                    v-if="request.shop_location"
                                    class="flex items-center gap-1.5 text-xs text-muted-foreground"
                                >
                                    <Icon name="MapPin" size="xs" />
                                    <span>
                                        {{ request.shop_location.city }},
                                        {{ request.shop_location.state }}
                                    </span>
                                </div>
                            </template>

                            <template #badge>
                                <div class="flex flex-col items-end gap-1">
                                    <span class="text-lg font-bold text-primary">
                                        {{ formatCurrency(request.price) }}
                                    </span>
                                </div>
                            </template>

                            <template #action>
                                <Button variant="ghost" size="sm">
                                    <span class="sr-only">View details</span>
                                    <Icon name="ChevronRight" size="sm" />
                                </Button>
                            </template>
                        </ListItem>
                    </template>

                    <!-- Empty State -->
                    <EmptyState
                        v-else
                        icon="FileX"
                        title="No available requests"
                        description="There are no open service requests in your area right now. Check back later or adjust your service area."
                        action-label="View All Requests"
                        action-href="/provider/available-requests"
                    />

                    <!-- View All Button -->
                    <div v-if="available_requests.length > 0" class="pt-3 border-t">
                        <Button
                            href="/provider/available-requests"
                            variant="outline"
                            class="w-full"
                        >
                            View All Requests
                            <Icon name="ArrowRight" size="sm" class="ml-2" />
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
