<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import ShopProfileTab from '@/components/settings/ShopProfileTab.vue';
import { type BreadcrumbItem } from '@/types';
import type { Shop, ShopLocation } from '@/types/marketplace';

type Props = {
    mustVerifyEmail: boolean;
    status?: string;
    shop?: Shop;
    shopLocations?: ShopLocation[];
    subtab?: string;
};

const props = withDefaults(defineProps<Props>(), {
    shopLocations: () => [],
    subtab: 'profile',
});

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Shop settings',
        href: '/settings/shop',
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Shop settings" />

        <h1 class="sr-only">Shop Settings</h1>

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <Heading
                    variant="small"
                    title="Shop profile"
                    description="Manage your shop information, locations, and preferences"
                />

                <div class="space-y-6">
                    <ShopProfileTab
                        :shop="shop"
                        :shop-locations="shopLocations"
                        :subtab="subtab"
                    />
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
