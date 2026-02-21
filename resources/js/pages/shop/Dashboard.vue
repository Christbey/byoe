<script setup lang="ts">
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { ServiceRequest, Booking } from '@/types/marketplace';
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

const pageTitle = computed(() =>
    props.shopName ? `${props.shopName} Dashboard` : 'Shop Dashboard',
);

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
    if (!time) return '';

    // Handle plain time strings like "08:00:00" or "08:00"
    const [hours, minutes] = time.split(':').map(Number);
    const period = hours >= 12 ? 'PM' : 'AM';
    const displayHours = hours % 12 || 12;

    return `${displayHours}:${minutes.toString().padStart(2, '0')} ${period}`;
};

const getRequestStatusVariant = (status: string) => {
    const variants: Record<string, 'success' | 'warning' | 'danger' | 'pending' | 'default'> = {
        open: 'success',
        pending_payment: 'warning',
        filled: 'default',
        cancelled: 'danger',
        expired: 'danger',
    };
    return variants[status] || 'default';
};

const getBookingStatusVariant = (status: string) => {
    const variants: Record<string, 'success' | 'warning' | 'danger' | 'pending' | 'default'> = {
        confirmed: 'success',
        pending: 'pending',
        completed: 'default',
        cancelled: 'danger',
    };
    return variants[status] || 'default';
};
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
            <!-- Page Header -->
            <PageHeader
                :title="pageTitle"
                description="Overview of your service requests and bookings"
            >
                <template #action>
                    <Button href="/shop/service-requests/create">
                        <Icon name="Plus" size="sm" class="mr-2" />
                        Create Request
                    </Button>
                </template>
                <template #help>
                    <PageHelp
                        storage-key="shop-dashboard"
                        :steps="[
                            'Post a service request to find a worker — set the date, time, skills needed, and pay rate.',
                            'Providers in your area will see and accept your request. You\'ll see the booking appear here.',
                            'Once the provider completes the shift, mark it complete and leave a rating.',
                            'Track your upcoming bookings and spending on this dashboard.',
                        ]"
                    />
                </template>
            </PageHeader>

            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-3">
                <StatCard
                    icon="FileText"
                    label="Active Requests"
                    :value="stats.active_requests"
                    subtitle="Open service requests"
                    variant="accent"
                />

                <StatCard
                    icon="CalendarCheck"
                    label="Upcoming Bookings"
                    :value="stats.upcoming_bookings"
                    subtitle="Next 7 days"
                />

                <StatCard
                    icon="DollarSign"
                    label="Total Spent"
                    :value="formatCurrency(stats.total_spent)"
                    subtitle="All time"
                />
            </div>

            <!-- Quick Actions -->
            <div class="flex flex-col sm:flex-row gap-3">
                <Button
                    href="/shop/service-requests/create"
                    class="w-full sm:w-auto"
                >
                    <Icon name="Plus" size="sm" class="mr-2" />
                    Create Request
                </Button>
                <Button
                    href="/shop/service-requests"
                    variant="outline"
                    class="w-full sm:w-auto"
                >
                    <Icon name="FileText" size="sm" class="mr-2" />
                    All Requests
                </Button>
                <Button
                    href="/shop/bookings"
                    variant="outline"
                    class="w-full sm:w-auto"
                >
                    <Icon name="Calendar" size="sm" class="mr-2" />
                    All Bookings
                </Button>
            </div>

            <!-- Two Column Layout -->
            <div class="grid gap-4 lg:grid-cols-2">
                <!-- Recent Service Requests -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle>Recent Service Requests</CardTitle>
                                <CardDescription>Your last 5 requests</CardDescription>
                            </div>
                            <Icon name="FileText" class="text-muted-foreground" />
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <template v-if="recent_requests.length > 0">
                            <ListItem
                                v-for="request in recent_requests"
                                :key="request.id"
                                :status="getRequestStatusVariant(request.status)"
                                clickable
                                @click="router.visit(`/shop/service-requests/${request.id}`)"
                            >
                                <template #header>
                                    <h3 class="font-semibold text-sm">
                                        {{ request.title }}
                                    </h3>
                                </template>

                                <template #content>
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
                                </template>

                                <template #footer>
                                    <div class="flex items-center gap-1.5 text-xs font-semibold text-foreground">
                                        <Icon name="DollarSign" size="xs" />
                                        <span>{{ formatCurrency(request.price) }}</span>
                                    </div>
                                </template>

                                <template #badge>
                                    <Badge :variant="request.status_variant">
                                        {{ request.status_label }}
                                    </Badge>
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
                            title="No service requests yet"
                            description="Create your first service request to find skilled providers in your area."
                            action-label="Create Request"
                            action-href="/shop/service-requests/create"
                        />

                        <!-- View All Button -->
                        <div v-if="recent_requests.length > 0" class="pt-3 border-t">
                            <Button
                                href="/shop/service-requests"
                                variant="outline"
                                class="w-full"
                            >
                                View All Requests
                                <Icon name="ArrowRight" size="sm" class="ml-2" />
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Upcoming Bookings -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle>Upcoming Bookings</CardTitle>
                                <CardDescription>
                                    Confirmed bookings in the next 7 days
                                </CardDescription>
                            </div>
                            <Icon name="CalendarDays" class="text-muted-foreground" />
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <template v-if="upcoming_bookings.length > 0">
                            <ListItem
                                v-for="booking in upcoming_bookings"
                                :key="booking.id"
                                :status="getBookingStatusVariant(booking.status)"
                                clickable
                                @click="router.visit(`/shop/bookings/${booking.id}`)"
                            >
                                <template #header>
                                    <h3 class="font-semibold text-sm">
                                        {{ booking.service_request?.title || 'Service' }}
                                    </h3>
                                </template>

                                <template #content>
                                    <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                        <Icon name="Calendar" size="xs" />
                                        <span>{{ formatDate(booking.service_request?.service_date || '') }}</span>
                                        <span>•</span>
                                        <Icon name="Clock" size="xs" />
                                        <span>{{ formatTime(booking.service_request?.start_time || '') }}</span>
                                    </div>
                                </template>

                                <template #footer>
                                    <div
                                        v-if="booking.provider"
                                        class="flex items-center gap-1.5 text-xs text-muted-foreground"
                                    >
                                        <Icon name="User" size="xs" />
                                        <span>{{ booking.provider.user?.name ?? '—' }}</span>
                                    </div>
                                </template>

                                <template #badge>
                                    <div class="flex flex-col items-end gap-1">
                                        <Badge :variant="booking.status_variant">
                                            {{ booking.status_label }}
                                        </Badge>
                                        <span class="text-sm font-semibold text-foreground">
                                            {{ formatCurrency(booking.service_price) }}
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
                            icon="CalendarOff"
                            title="No upcoming bookings"
                            description="Your confirmed bookings will appear here once providers accept your service requests."
                        />

                        <!-- View All Button -->
                        <div v-if="upcoming_bookings.length > 0" class="pt-3 border-t">
                            <Button href="/shop/bookings" variant="outline" class="w-full">
                                View All Bookings
                                <Icon name="ArrowRight" size="sm" class="ml-2" />
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
