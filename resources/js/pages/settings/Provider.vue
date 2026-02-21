<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import ProviderProfileTab from '@/components/settings/ProviderProfileTab.vue';
import { type BreadcrumbItem } from '@/types';
import type { AvailabilityDay, Provider, Rating } from '@/types/marketplace';

interface Completeness {
    percentage: number;
    steps: Record<string, boolean>;
}

interface StarBreakdown {
    count: number;
    percentage: number;
}

type Props = {
    mustVerifyEmail: boolean;
    status?: string;
    provider?: Provider | null;
    canReceivePayouts?: boolean;
    isStripeOnboarded?: boolean;
    completeness?: Completeness;
    schedule?: Record<string, AvailabilityDay>;
    blackoutDates?: string[];
    minNoticeHours?: number;
    recentRatings?: Rating[];
    starBreakdown?: Record<number, StarBreakdown>;
};

const props = withDefaults(defineProps<Props>(), {
    provider: null,
    canReceivePayouts: false,
    isStripeOnboarded: false,
    schedule: () => ({}),
    blackoutDates: () => [],
    minNoticeHours: 24,
    recentRatings: () => [],
    starBreakdown: () => ({}),
});

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Provider settings',
        href: '/settings/provider',
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Provider settings" />

        <h1 class="sr-only">Provider Settings</h1>

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <Heading
                    variant="small"
                    title="Provider profile"
                    description="Manage your provider profile, availability, and earnings"
                />

                <div class="space-y-6">
                    <ProviderProfileTab
                        :provider="provider"
                        :can-receive-payouts="canReceivePayouts"
                        :is-stripe-onboarded="isStripeOnboarded"
                        :completeness="completeness"
                        :schedule="schedule"
                        :blackout-dates="blackoutDates"
                        :min-notice-hours="minNoticeHours"
                        :recent-ratings="recentRatings"
                        :star-breakdown="starBreakdown"
                    />
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
