<script setup lang="ts">
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import PersonalProfileTab from '@/components/settings/PersonalProfileTab.vue';
import ProviderProfileTab from '@/components/settings/ProviderProfileTab.vue';
import ShopProfileTab from '@/components/settings/ShopProfileTab.vue';
import { type BreadcrumbItem } from '@/types';
import type { AvailabilityDay, Provider, Rating, Shop, ShopLocation } from '@/types/marketplace';
import { edit } from '@/routes/profile';

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
    activeTab?: string;
    canAccessProvider?: boolean;
    canAccessShop?: boolean;
    // Provider data
    provider?: Provider | null;
    canReceivePayouts?: boolean;
    isStripeOnboarded?: boolean;
    completeness?: Completeness;
    schedule?: Record<string, AvailabilityDay>;
    blackoutDates?: string[];
    minNoticeHours?: number;
    recentRatings?: Rating[];
    starBreakdown?: Record<number, StarBreakdown>;
    // Shop data
    shop?: Shop;
    shopLocations?: ShopLocation[];
    subtab?: string;
};

const props = withDefaults(defineProps<Props>(), {
    activeTab: 'personal',
    canAccessProvider: false,
    canAccessShop: false,
    provider: null,
    canReceivePayouts: false,
    isStripeOnboarded: false,
    schedule: () => ({}),
    blackoutDates: () => [],
    minNoticeHours: 24,
    recentRatings: () => [],
    starBreakdown: () => ({}),
    shopLocations: () => [],
    subtab: 'profile',
});

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: edit().url,
    },
];

// Determine which tabs are available
const availableTabs = computed(() => {
    const tabs = ['personal'];
    if (props.canAccessProvider) tabs.push('provider');
    if (props.canAccessShop) tabs.push('shop');
    return tabs;
});

// Ensure active tab is valid
const currentTab = computed(() => {
    if (availableTabs.value.includes(props.activeTab)) {
        return props.activeTab;
    }
    return availableTabs.value[0];
});

const switchTab = (tab: string) => {
    router.get('/settings/profile', { tab }, { preserveState: false });
};

const tabTitle = computed(() => {
    const titles: Record<string, string> = {
        personal: 'Profile information',
        provider: 'Provider profile',
        shop: 'Shop profile',
    };
    return titles[currentTab.value] || 'Profile information';
});

const tabDescription = computed(() => {
    const descriptions: Record<string, string> = {
        personal: 'Update your name and email address',
        provider: 'Manage your provider profile and availability',
        shop: 'Manage your shop information and locations',
    };
    return descriptions[currentTab.value] || '';
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Profile settings" />

        <h1 class="sr-only">Profile Settings</h1>

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <Heading
                    variant="small"
                    :title="tabTitle"
                    :description="tabDescription"
                />

                <!-- Tabs Navigation - only show if user has multiple profile types -->
                <div v-if="availableTabs.length > 1" class="flex gap-1 border-b">
                    <button
                        v-if="availableTabs.includes('personal')"
                        class="px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px"
                        :class="currentTab === 'personal' ? 'border-primary text-foreground' : 'border-transparent text-muted-foreground hover:text-foreground'"
                        @click="switchTab('personal')"
                    >
                        Personal
                    </button>
                    <button
                        v-if="canAccessProvider"
                        class="px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px"
                        :class="currentTab === 'provider' ? 'border-primary text-foreground' : 'border-transparent text-muted-foreground hover:text-foreground'"
                        @click="switchTab('provider')"
                    >
                        Provider
                    </button>
                    <button
                        v-if="canAccessShop"
                        class="px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px"
                        :class="currentTab === 'shop' ? 'border-primary text-foreground' : 'border-transparent text-muted-foreground hover:text-foreground'"
                        @click="switchTab('shop')"
                    >
                        Shop
                    </button>
                </div>

                <!-- Tab Content -->
                <div class="space-y-6">
                    <PersonalProfileTab
                        v-if="currentTab === 'personal'"
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                    />

                    <ProviderProfileTab
                        v-if="currentTab === 'provider' && canAccessProvider"
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

                    <ShopProfileTab
                        v-if="currentTab === 'shop' && canAccessShop"
                        :shop="shop"
                        :shop-locations="shopLocations"
                        :subtab="subtab"
                    />

                    <!-- Delete User - only show on personal tab -->
                    <DeleteUser v-if="currentTab === 'personal'" />
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
