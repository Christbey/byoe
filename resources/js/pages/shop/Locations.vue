<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { ShopLocation } from '@/types/marketplace';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';

interface Props {
    locations: ShopLocation[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Shop Locations',
        href: '/shop/locations',
    },
];

const deletingLocationId = ref<number | null>(null);

const handleDeleteLocation = (locationId: number) => {
    if (
        confirm(
            'Are you sure you want to delete this location? This action cannot be undone.',
        )
    ) {
        deletingLocationId.value = locationId;
        router.delete(`/shop/locations/${locationId}`, {
            preserveScroll: true,
            onFinish: () => {
                deletingLocationId.value = null;
            },
        });
    }
};

const formatAddress = (location: ShopLocation) => {
    const parts = [
        location.address_line_1,
        location.address_line_2,
        `${location.city}, ${location.state} ${location.zip_code}`,
    ].filter(Boolean);
    return parts.join(', ');
};

const getGeocodingStatus = (location: ShopLocation) => {
    if (location.geocoded_at && location.latitude && location.longitude) {
        return {
            label: 'Geocoded',
            variant: 'default' as const,
        };
    }
    return {
        label: 'Not Geocoded',
        variant: 'secondary' as const,
    };
};
</script>

<template>
    <Head title="Shop Locations" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6 max-w-4xl">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-2">
                    <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                        Shop Locations
                    </h1>
                    <p class="text-sm text-muted-foreground md:text-base">
                        Manage your shop locations
                    </p>
                </div>
                <Button
                    as="a"
                    href="/shop/locations/create"
                    class="w-full sm:w-auto"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                        class="size-5 mr-2"
                    >
                        <path
                            d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z"
                        />
                    </svg>
                    Add New Location
                </Button>
            </div>

            <!-- Locations List -->
            <div v-if="locations.length > 0" class="space-y-4">
                <Card
                    v-for="location in locations"
                    :key="location.id"
                    class="relative"
                >
                    <CardHeader>
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 space-y-2">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <CardTitle>
                                        {{ location.address_line_1 }}
                                    </CardTitle>
                                    <Badge
                                        v-if="location.is_primary"
                                        variant="default"
                                    >
                                        Primary
                                    </Badge>
                                </div>
                                <div class="text-sm text-muted-foreground space-y-1">
                                    <p v-if="location.address_line_2">
                                        {{ location.address_line_2 }}
                                    </p>
                                    <p>
                                        {{ location.city }}, {{ location.state }}
                                        {{ location.zip_code }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-start gap-2">
                                <Button
                                    as="a"
                                    :href="`/shop/locations/${location.id}/edit`"
                                    variant="outline"
                                    size="sm"
                                >
                                    Edit
                                </Button>
                                <Button
                                    v-if="!location.is_primary"
                                    variant="ghost"
                                    size="sm"
                                    :disabled="deletingLocationId === location.id"
                                    @click="handleDeleteLocation(location.id)"
                                >
                                    <svg
                                        v-if="deletingLocationId === location.id"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                        class="size-5 animate-spin"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M15.312 11.424a5.5 5.5 0 01-9.201 2.466l-.312-.311h2.433a.75.75 0 000-1.5H3.989a.75.75 0 00-.75.75v4.242a.75.75 0 001.5 0v-2.43l.31.31a7 7 0 0011.712-3.138.75.75 0 00-1.449-.39zm1.23-3.723a.75.75 0 00.219-.53V2.929a.75.75 0 00-1.5 0V5.36l-.31-.31A7 7 0 003.239 8.188a.75.75 0 101.448.389A5.5 5.5 0 0113.89 6.11l.311.31h-2.432a.75.75 0 000 1.5h4.243a.75.75 0 00.53-.219z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    <svg
                                        v-else
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                        class="size-5"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <!-- Geocoding Status -->
                        <div class="flex items-center gap-2">
                            <Badge
                                :variant="getGeocodingStatus(location).variant"
                            >
                                {{ getGeocodingStatus(location).label }}
                            </Badge>
                            <span
                                v-if="location.latitude && location.longitude"
                                class="text-xs text-muted-foreground"
                            >
                                {{ location.latitude.toFixed(6) }},
                                {{ location.longitude.toFixed(6) }}
                            </span>
                        </div>

                        <!-- Full Address Display -->
                        <div class="rounded-lg border border-border bg-muted/50 p-3">
                            <p class="text-sm font-medium mb-1">
                                Full Address
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{ formatAddress(location) }}
                            </p>
                        </div>

                        <!-- Created Date -->
                        <p class="text-xs text-muted-foreground">
                            Added on
                            {{
                                new Date(location.created_at).toLocaleDateString(
                                    'en-US',
                                    {
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric',
                                    },
                                )
                            }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <Card v-else>
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
                            d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"
                        />
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"
                        />
                    </svg>
                    <h3 class="text-lg font-semibold mb-2">
                        No locations added yet
                    </h3>
                    <p class="text-sm text-muted-foreground max-w-md mb-4">
                        Add your first location to start receiving service
                        requests from providers in your area.
                    </p>
                    <Button as="a" href="/shop/locations/create">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            class="size-5 mr-2"
                        >
                            <path
                                d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z"
                            />
                        </svg>
                        Add Your First Location
                    </Button>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
