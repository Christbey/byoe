<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { ServiceRequest } from '@/types/marketplace';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';
import PageHelp from '@/components/marketplace/PageHelp.vue';
import PaymentForm from '@/components/marketplace/PaymentForm.vue';
import { ref } from 'vue';

interface Props {
    serviceRequest: ServiceRequest;
    clientSecret: string | null;
    stripePublishableKey: string | null;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Service Requests',
        href: '/shop/service-requests',
    },
    {
        title: props.serviceRequest.title,
        href: `/shop/service-requests/${props.serviceRequest.id}`,
    },
];

const isCancelling = ref(false);

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
};

const formatDate = (date: string) => {
    return new Intl.DateTimeFormat('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    }).format(new Date(date));
};

const formatDateTime = (datetime: string) => {
    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    }).format(new Date(datetime));
};

const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
        pending_payment: 'secondary',
        open: 'default',
        filled: 'secondary',
        expired: 'destructive',
        cancelled: 'outline',
        pending: 'default',
        confirmed: 'default',
        in_progress: 'default',
        completed: 'secondary',
    };
    return colors[status] || 'outline';
};

const requestStatusLabel = (status: string) => {
    const labels: Record<string, string> = {
        pending_payment: 'Payment Required',
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

const handleCancelRequest = () => {
    if (
        confirm(
            'Are you sure you want to cancel this service request? This action cannot be undone.',
        )
    ) {
        isCancelling.value = true;
        router.post(
            `/shop/service-requests/${props.serviceRequest.id}/cancel`,
            {},
            {
                onFinish: () => {
                    isCancelling.value = false;
                },
            },
        );
    }
};

const handlePaymentSuccess = () => {
    router.post(`/shop/service-requests/${props.serviceRequest.id}/confirm-payment`);
};
</script>

<template>
    <Head :title="serviceRequest.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-2">
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                            {{ serviceRequest.title }}
                        </h1>
                        <Badge :variant="getStatusColor(serviceRequest.status)">
                            {{ requestStatusLabel(serviceRequest.status) }}
                        </Badge>
                    </div>
                    <p class="text-sm text-muted-foreground md:text-base">
                        Posted {{ formatDate(serviceRequest.created_at) }}
                    </p>
                    <PageHelp
                        v-if="serviceRequest.status !== 'pending_payment'"
                        storage-key="shop-show-request"
                        :steps="[
                            'This request is live and visible to providers. Once someone accepts, a booking is created automatically.',
                            'The status changes to \'Booked\' when a provider accepts — you\'ll see their details in the Booking Information section.',
                            'You can cancel this request any time while it\'s still \'Open\'.',
                            'If the request expires without being accepted, it will close automatically.',
                        ]"
                    />
                </div>
                <div class="flex gap-2">
                    <Button
                        as="a"
                        href="/shop/service-requests"
                        variant="outline"
                    >
                        Back to List
                    </Button>
                    <Button
                        v-if="serviceRequest.status === 'open' || serviceRequest.status === 'pending_payment'"
                        variant="destructive"
                        :disabled="isCancelling"
                        @click="handleCancelRequest"
                    >
                        Cancel Request
                    </Button>
                </div>
            </div>

            <!-- Completion Banner -->
            <Card
                v-if="serviceRequest.booking?.status === 'completed'"
                class="border-green-300 bg-green-50 dark:bg-green-950/20"
            >
                <CardContent class="flex items-center gap-4 py-5">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/40">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-6 text-green-600 dark:text-green-400">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-green-800 dark:text-green-300">Service Completed</p>
                        <p class="text-sm text-green-700 dark:text-green-400">
                            This service was completed on
                            {{ serviceRequest.booking.completed_at ? formatDateTime(serviceRequest.booking.completed_at) : 'N/A' }}.
                        </p>
                    </div>
                    <Button as="a" :href="`/shop/bookings/${serviceRequest.booking.id}`" variant="outline" size="sm">
                        View Booking
                    </Button>
                </CardContent>
            </Card>

            <!-- Main Content -->
            <div class="grid gap-4 lg:grid-cols-3">
                <!-- Left Column - Details -->
                <div class="lg:col-span-2 space-y-4">
                    <!-- Payment Required -->
                    <Card
                        v-if="serviceRequest.status === 'pending_payment' && clientSecret && stripePublishableKey"
                        class="border-amber-300 bg-amber-50 dark:bg-amber-950/20"
                    >
                        <CardHeader>
                            <CardTitle class="text-amber-800 dark:text-amber-300">
                                Authorize Payment to Go Live
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <p class="text-sm text-amber-700 dark:text-amber-400">
                                Your card will be authorized for
                                <strong>{{ formatCurrency(serviceRequest.price) }}</strong>.
                                You won't be charged until a provider accepts your request — and if no one accepts before it expires, the hold is released automatically.
                            </p>
                            <PaymentForm
                                :client-secret="clientSecret"
                                :publishable-key="stripePublishableKey"
                                :amount="serviceRequest.price"
                                button-label="Authorize Payment & Go Live"
                                @success="handlePaymentSuccess"
                            />
                        </CardContent>
                    </Card>

                    <!-- Description -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Description</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-sm whitespace-pre-wrap">
                                {{ serviceRequest.description }}
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Skills Required -->
                    <Card
                        v-if="serviceRequest.skills_required && serviceRequest.skills_required.length > 0"
                    >
                        <CardHeader>
                            <CardTitle>Skills Required</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex flex-wrap gap-2">
                                <Badge
                                    v-for="skill in serviceRequest.skills_required"
                                    :key="skill"
                                    variant="outline"
                                >
                                    {{ skill }}
                                </Badge>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Location -->
                    <Card v-if="serviceRequest.shop_location">
                        <CardHeader>
                            <CardTitle>Location</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex items-start gap-3">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                    class="size-5 shrink-0 text-muted-foreground mt-0.5"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 103 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 002.273 1.765 11.842 11.842 0 00.976.544l.062.029.018.008.006.003zM10 11.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                <div class="text-sm">
                                    <p class="text-muted-foreground">
                                        {{ serviceRequest.shop_location.address_line_1 }}
                                    </p>
                                    <p
                                        v-if="serviceRequest.shop_location.address_line_2"
                                        class="text-muted-foreground"
                                    >
                                        {{ serviceRequest.shop_location.address_line_2 }}
                                    </p>
                                    <p class="text-muted-foreground">
                                        {{ serviceRequest.shop_location.city }},
                                        {{ serviceRequest.shop_location.state }}
                                        {{ serviceRequest.shop_location.zip_code }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Booking Info (if filled) -->
                    <Card v-if="serviceRequest.booking">
                        <CardHeader>
                            <CardTitle>Booking Information</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <!-- Provider -->
                                <div class="flex items-center gap-3 rounded-lg bg-muted/50 p-3">
                                    <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary/10">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5 text-primary">
                                            <path d="M10 8a3 3 0 100-6 3 3 0 000 6zM3.465 14.493a1.23 1.23 0 00.41 1.412A9.957 9.957 0 0010 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 00-13.074.003z" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium">
                                            {{ serviceRequest.booking.provider?.user?.name || 'Not yet assigned' }}
                                        </p>
                                        <p v-if="serviceRequest.booking.provider" class="text-xs text-muted-foreground">
                                            {{ serviceRequest.booking.provider.years_experience }} yrs exp ·
                                            ⭐ {{ Number(serviceRequest.booking.provider.average_rating).toFixed(1) }}
                                        </p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <p class="font-medium text-muted-foreground">Booking Status</p>
                                        <Badge :variant="getStatusColor(serviceRequest.booking.status)" class="mt-1">
                                            {{ bookingStatusLabel(serviceRequest.booking.status) }}
                                        </Badge>
                                    </div>
                                    <div>
                                        <p class="font-medium text-muted-foreground">Service Price</p>
                                        <p class="font-semibold">{{ formatCurrency(serviceRequest.booking.service_price) }}</p>
                                    </div>
                                    <div v-if="serviceRequest.booking.completed_at">
                                        <p class="font-medium text-muted-foreground">Completed</p>
                                        <p>{{ formatDateTime(serviceRequest.booking.completed_at) }}</p>
                                    </div>
                                    <div v-if="serviceRequest.booking.provider_payout">
                                        <p class="font-medium text-muted-foreground">Provider Payout</p>
                                        <p>{{ formatCurrency(serviceRequest.booking.provider_payout) }}</p>
                                    </div>
                                </div>

                                <Button
                                    as="a"
                                    :href="`/shop/bookings/${serviceRequest.booking.id}`"
                                    variant="outline"
                                    class="w-full"
                                >
                                    View Full Booking Details
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Right Column - Summary -->
                <div class="space-y-4">
                    <!-- Schedule & Price -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Schedule & Price</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">
                                    Service Date
                                </p>
                                <p class="text-lg font-semibold">
                                    {{ formatDate(serviceRequest.service_date) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">
                                    Time
                                </p>
                                <p class="text-lg font-semibold">
                                    {{ formatDateTime(serviceRequest.start_time) }}
                                    -
                                    {{ formatDateTime(serviceRequest.end_time) }}
                                </p>
                            </div>
                            <div class="pt-2 border-t">
                                <p class="text-sm font-medium text-muted-foreground">
                                    Total Price
                                </p>
                                <p class="text-2xl font-bold">
                                    {{ formatCurrency(serviceRequest.price) }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Status Info -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Status</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">
                                    Current Status
                                </p>
                                <Badge
                                    :variant="getStatusColor(serviceRequest.status)"
                                    class="mt-1"
                                >
                                    {{ requestStatusLabel(serviceRequest.status) }}
                                </Badge>
                            </div>
                            <div v-if="serviceRequest.status === 'open'">
                                <p class="text-sm font-medium text-muted-foreground">
                                    Expires
                                </p>
                                <p class="text-sm">
                                    {{ formatDateTime(serviceRequest.expires_at) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">
                                    Created
                                </p>
                                <p class="text-sm">
                                    {{ formatDateTime(serviceRequest.created_at) }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
