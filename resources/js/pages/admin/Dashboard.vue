<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';

interface AdminStats {
    total_users: number;
    active_shops: number;
    active_providers: number;
    total_bookings: number;
    platform_fees_collected: number;
    disputes_requiring_attention: number;
}

interface ActivityItem {
    id: number;
    type: string;
    description: string;
    created_at: string;
}

interface RevenueData {
    labels: string[];
    values: number[];
}

interface Props {
    stats: AdminStats;
    recent_activity: ActivityItem[];
    revenue_data: RevenueData;
    system_health: {
        database: boolean;
        cache: boolean;
        queue: boolean;
        storage: boolean;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin Dashboard',
        href: '/admin/dashboard',
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
        hour: 'numeric',
        minute: '2-digit',
    }).format(new Date(date));
};
</script>

<template>
    <Head title="Admin Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
            <!-- Header -->
            <div class="space-y-2">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                    Admin Dashboard
                </h1>
                <p class="text-sm text-muted-foreground md:text-base">
                    System overview and key metrics
                </p>
            </div>

            <!-- Key Metrics -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader>
                        <CardDescription>Total Users</CardDescription>
                        <CardTitle class="text-3xl">
                            {{ stats.total_users }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-muted-foreground">
                            All registered users
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardDescription>Active Shops</CardDescription>
                        <CardTitle class="text-3xl">
                            {{ stats.active_shops }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-muted-foreground">
                            Currently active
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardDescription>Active Providers</CardDescription>
                        <CardTitle class="text-3xl">
                            {{ stats.active_providers }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-muted-foreground">
                            Available for bookings
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardDescription>Total Bookings</CardDescription>
                        <CardTitle class="text-3xl">
                            {{ stats.total_bookings }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-muted-foreground">
                            All time
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Revenue & Disputes -->
            <div class="grid gap-4 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Platform Revenue</CardTitle>
                        <CardDescription>
                            Platform fees collected this month
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <p class="text-4xl font-bold">
                            {{ formatCurrency(stats.platform_fees_collected) }}
                        </p>
                        <div class="mt-4 h-32 flex items-end gap-1">
                            <div
                                v-for="(value, index) in revenue_data.values"
                                :key="index"
                                class="flex-1 bg-primary rounded-t transition-all hover:opacity-80"
                                :style="{
                                    height: `${(value / Math.max(...revenue_data.values)) * 100}%`,
                                }"
                                :title="`${revenue_data.labels[index]}: ${formatCurrency(value)}`"
                            ></div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Disputes Requiring Attention</CardTitle>
                        <CardDescription>
                            Active disputes needing review
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center gap-4">
                            <p class="text-4xl font-bold">
                                {{ stats.disputes_requiring_attention }}
                            </p>
                            <Button
                                as="a"
                                href="/admin/disputes"
                                variant="outline"
                                size="sm"
                            >
                                View Disputes
                            </Button>
                        </div>
                        <p
                            v-if="stats.disputes_requiring_attention > 0"
                            class="text-sm text-destructive mt-4"
                        >
                            {{ stats.disputes_requiring_attention }} dispute{{
                                stats.disputes_requiring_attention > 1 ? 's' : ''
                            }}
                            require immediate attention
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Recent Activity & System Health -->
            <div class="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Recent Activity</CardTitle>
                        <CardDescription>
                            Latest system events
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div
                            v-if="recent_activity.length > 0"
                            class="space-y-2"
                        >
                            <div
                                v-for="activity in recent_activity"
                                :key="activity.id"
                                class="flex items-start gap-3 p-2 hover:bg-accent/50 rounded transition-colors"
                            >
                                <div class="size-2 rounded-full bg-primary mt-2 shrink-0"></div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm">
                                        {{ activity.description }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ formatDate(activity.created_at) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div
                            v-else
                            class="text-center py-8 text-sm text-muted-foreground"
                        >
                            No recent activity
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>System Health</CardTitle>
                        <CardDescription>
                            Service status indicators
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-3 border rounded-lg">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="size-3 rounded-full"
                                        :class="
                                            system_health.database
                                                ? 'bg-green-500'
                                                : 'bg-red-500'
                                        "
                                    ></div>
                                    <p class="text-sm font-medium">Database</p>
                                </div>
                                <p class="text-xs text-muted-foreground mt-1">
                                    {{
                                        system_health.database
                                            ? 'Operational'
                                            : 'Issues detected'
                                    }}
                                </p>
                            </div>

                            <div class="p-3 border rounded-lg">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="size-3 rounded-full"
                                        :class="
                                            system_health.cache
                                                ? 'bg-green-500'
                                                : 'bg-red-500'
                                        "
                                    ></div>
                                    <p class="text-sm font-medium">Cache</p>
                                </div>
                                <p class="text-xs text-muted-foreground mt-1">
                                    {{
                                        system_health.cache
                                            ? 'Operational'
                                            : 'Issues detected'
                                    }}
                                </p>
                            </div>

                            <div class="p-3 border rounded-lg">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="size-3 rounded-full"
                                        :class="
                                            system_health.queue
                                                ? 'bg-green-500'
                                                : 'bg-red-500'
                                        "
                                    ></div>
                                    <p class="text-sm font-medium">Queue</p>
                                </div>
                                <p class="text-xs text-muted-foreground mt-1">
                                    {{
                                        system_health.queue
                                            ? 'Operational'
                                            : 'Issues detected'
                                    }}
                                </p>
                            </div>

                            <div class="p-3 border rounded-lg">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="size-3 rounded-full"
                                        :class="
                                            system_health.storage
                                                ? 'bg-green-500'
                                                : 'bg-red-500'
                                        "
                                    ></div>
                                    <p class="text-sm font-medium">Storage</p>
                                </div>
                                <p class="text-xs text-muted-foreground mt-1">
                                    {{
                                        system_health.storage
                                            ? 'Operational'
                                            : 'Issues detected'
                                    }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Quick Actions -->
            <Card>
                <CardHeader>
                    <CardTitle>Quick Actions</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex flex-wrap gap-3">
                        <Button as="a" href="/admin/users" variant="outline">
                            Manage Users
                        </Button>
                        <Button as="a" href="/admin/disputes" variant="outline">
                            View Disputes
                        </Button>
                        <Button as="a" href="/admin/audit-logs" variant="outline">
                            Audit Logs
                        </Button>
                        <Button as="a" href="/admin/settings" variant="outline">
                            System Settings
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
