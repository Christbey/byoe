<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardFooter from '@/components/ui/card/CardFooter.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface MarketplaceConfig {
    platform_fee_percentage: number;
    booking_advance_hours: number;
    booking_cancellation_hours: number;
    request_expiry_days: number;
    payout_delay_days: number;
    max_service_hours: number;
    min_service_price: number;
    max_service_price: number;
}

interface Props {
    config: MarketplaceConfig;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Settings',
        href: '/admin/settings',
    },
];

const form = useForm({
    platform_fee_percentage: props.config.platform_fee_percentage,
    booking_advance_hours: props.config.booking_advance_hours,
    booking_cancellation_hours: props.config.booking_cancellation_hours,
    request_expiry_days: props.config.request_expiry_days,
    payout_delay_days: props.config.payout_delay_days,
    max_service_hours: props.config.max_service_hours,
    min_service_price: props.config.min_service_price,
    max_service_price: props.config.max_service_price,
});

const handleSubmit = () => {
    form.put('/admin/settings', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="System Settings" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6 max-w-4xl">
            <!-- Header -->
            <div class="space-y-2">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                    System Settings
                </h1>
                <p class="text-sm text-muted-foreground md:text-base">
                    Configure marketplace rules and policies
                </p>
            </div>

            <form @submit.prevent="handleSubmit" class="space-y-4">
                <!-- Platform Fees -->
                <Card>
                    <CardHeader>
                        <CardTitle>Platform Fees</CardTitle>
                        <CardDescription>
                            Configure commission and fee structure
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="space-y-2">
                            <Label for="platform_fee_percentage">
                                Platform Fee Percentage
                            </Label>
                            <div class="flex items-center gap-2">
                                <Input
                                    id="platform_fee_percentage"
                                    v-model="form.platform_fee_percentage"
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    required
                                    class="max-w-xs"
                                />
                                <span class="text-sm text-muted-foreground">%</span>
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Percentage deducted from each booking for platform
                                fees (default: 15%)
                            </p>
                            <p
                                v-if="form.errors.platform_fee_percentage"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.platform_fee_percentage }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Booking Windows -->
                <Card>
                    <CardHeader>
                        <CardTitle>Booking Windows</CardTitle>
                        <CardDescription>
                            Set timing constraints for bookings
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="booking_advance_hours">
                                    Minimum Advance Booking
                                </Label>
                                <div class="flex items-center gap-2">
                                    <Input
                                        id="booking_advance_hours"
                                        v-model="form.booking_advance_hours"
                                        type="number"
                                        min="1"
                                        max="168"
                                        required
                                        class="max-w-xs"
                                    />
                                    <span class="text-sm text-muted-foreground">
                                        hours
                                    </span>
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    Minimum hours before service date to book
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="booking_cancellation_hours">
                                    Cancellation Window
                                </Label>
                                <div class="flex items-center gap-2">
                                    <Input
                                        id="booking_cancellation_hours"
                                        v-model="form.booking_cancellation_hours"
                                        type="number"
                                        min="1"
                                        max="168"
                                        required
                                        class="max-w-xs"
                                    />
                                    <span class="text-sm text-muted-foreground">
                                        hours
                                    </span>
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    Hours before service to allow cancellations
                                </p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="request_expiry_days">
                                Request Expiry Period
                            </Label>
                            <div class="flex items-center gap-2">
                                <Input
                                    id="request_expiry_days"
                                    v-model="form.request_expiry_days"
                                    type="number"
                                    min="1"
                                    max="30"
                                    required
                                    class="max-w-xs"
                                />
                                <span class="text-sm text-muted-foreground">
                                    days
                                </span>
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Default days until service request expires
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Payout Configuration -->
                <Card>
                    <CardHeader>
                        <CardTitle>Payout Configuration</CardTitle>
                        <CardDescription>
                            Configure provider payout timing
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="space-y-2">
                            <Label for="payout_delay_days">
                                Payout Delay Period
                            </Label>
                            <div class="flex items-center gap-2">
                                <Input
                                    id="payout_delay_days"
                                    v-model="form.payout_delay_days"
                                    type="number"
                                    min="0"
                                    max="30"
                                    required
                                    class="max-w-xs"
                                />
                                <span class="text-sm text-muted-foreground">
                                    days
                                </span>
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Days to wait after booking completion before
                                issuing payout
                            </p>
                            <p
                                v-if="form.errors.payout_delay_days"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.payout_delay_days }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Service Constraints -->
                <Card>
                    <CardHeader>
                        <CardTitle>Service Constraints</CardTitle>
                        <CardDescription>
                            Set limits for service bookings
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="space-y-2">
                            <Label for="max_service_hours">
                                Maximum Service Duration
                            </Label>
                            <div class="flex items-center gap-2">
                                <Input
                                    id="max_service_hours"
                                    v-model="form.max_service_hours"
                                    type="number"
                                    min="1"
                                    max="24"
                                    required
                                    class="max-w-xs"
                                />
                                <span class="text-sm text-muted-foreground">
                                    hours
                                </span>
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Maximum hours for a single service booking
                            </p>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="min_service_price">
                                    Minimum Service Price
                                </Label>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-muted-foreground">
                                        $
                                    </span>
                                    <Input
                                        id="min_service_price"
                                        v-model="form.min_service_price"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        required
                                        class="max-w-xs"
                                    />
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    Minimum price for service requests
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="max_service_price">
                                    Maximum Service Price
                                </Label>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-muted-foreground">
                                        $
                                    </span>
                                    <Input
                                        id="max_service_price"
                                        v-model="form.max_service_price"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        required
                                        class="max-w-xs"
                                    />
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    Maximum price for service requests
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Warning Notice -->
                <div class="rounded-lg border border-yellow-500/50 bg-yellow-500/10 p-4">
                    <div class="flex gap-3">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            class="size-6 text-yellow-600 shrink-0"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        <div class="space-y-1">
                            <p class="font-medium text-sm">Important Notice</p>
                            <p class="text-sm text-muted-foreground">
                                Changes to these settings will affect all future
                                bookings and requests. Existing bookings will not
                                be modified. Always review changes carefully before
                                saving.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <Card>
                    <CardFooter class="flex flex-col gap-3 sm:flex-row pt-6">
                        <Button
                            type="submit"
                            :disabled="form.processing || !form.isDirty"
                            class="w-full sm:flex-1"
                        >
                            <svg
                                v-if="form.processing"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                                class="size-5 mr-2 animate-spin"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M15.312 11.424a5.5 5.5 0 01-9.201 2.466l-.312-.311h2.433a.75.75 0 000-1.5H3.989a.75.75 0 00-.75.75v4.242a.75.75 0 001.5 0v-2.43l.31.31a7 7 0 0011.712-3.138.75.75 0 00-1.449-.39zm1.23-3.723a.75.75 0 00.219-.53V2.929a.75.75 0 00-1.5 0V5.36l-.31-.31A7 7 0 003.239 8.188a.75.75 0 101.448.389A5.5 5.5 0 0113.89 6.11l.311.31h-2.432a.75.75 0 000 1.5h4.243a.75.75 0 00.53-.219z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <span v-if="form.processing">Saving...</span>
                            <span v-else>Save All Settings</span>
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            as="a"
                            href="/admin/dashboard"
                            :disabled="form.processing"
                            class="w-full sm:w-auto sm:flex-1"
                        >
                            Cancel
                        </Button>
                    </CardFooter>
                </Card>
            </form>
        </div>
    </AppLayout>
</template>
