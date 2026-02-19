<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { Shop, ShopLocation } from '@/types/marketplace';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';

interface Props {
    shop: Shop;
    tab?: string;
    locations: ShopLocation[];
}

const props = withDefaults(defineProps<Props>(), {
    tab: 'profile',
});

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Shop Profile', href: '/shop/profile' }];

const deletingLocationId = ref<number | null>(null);

const switchTab = (tab: string) => {
    router.get('/shop/profile', { tab }, { preserveState: false });
};

// ─── Profile helpers ──────────────────────────────────────────────────────────

const maskEin = (ein: string) => {
    const digits = ein.replace('-', '');
    return digits.slice(0, 2) + '-***' + digits.slice(5);
};

const statusVariant = (status: string) => {
    const map: Record<string, string> = { active: 'default', inactive: 'secondary', suspended: 'destructive' };
    return map[status] || 'outline';
};

// ─── Location helpers ─────────────────────────────────────────────────────────

const formatAddress = (location: ShopLocation) => {
    return [location.address_line_1, location.address_line_2, `${location.city}, ${location.state} ${location.zip_code}`]
        .filter(Boolean)
        .join(', ');
};

const getGeocodingStatus = (location: ShopLocation) => {
    if (location.geocoded_at && location.latitude && location.longitude) {
        return { label: 'Geocoded', variant: 'default' as const };
    }
    return { label: 'Not Geocoded', variant: 'secondary' as const };
};

const handleDeleteLocation = (locationId: number) => {
    if (confirm('Are you sure you want to delete this location? This action cannot be undone.')) {
        deletingLocationId.value = locationId;
        router.delete(`/shop/locations/${locationId}`, {
            preserveScroll: true,
            onFinish: () => { deletingLocationId.value = null; },
        });
    }
};
</script>

<template>
    <Head title="Shop Profile" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6 max-w-4xl">
            <!-- Header -->
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                            {{ shop.name || 'Your Shop' }}
                        </h1>
                        <Badge v-if="shop.id" :variant="statusVariant(shop.status)">
                            {{ shop.status.charAt(0).toUpperCase() + shop.status.slice(1) }}
                        </Badge>
                    </div>
                    <p class="text-sm text-muted-foreground">Manage your shop information and locations</p>
                </div>
                <Link href="/shop/profile/edit">
                    <Button variant="outline" size="sm">Edit Profile</Button>
                </Link>
            </div>

            <!-- No shop yet -->
            <Card v-if="!shop.id" class="border-dashed">
                <CardContent class="py-12 text-center space-y-4">
                    <p class="text-muted-foreground">You haven't set up your shop profile yet.</p>
                    <Link href="/shop/profile/edit">
                        <Button>Set Up Shop Profile</Button>
                    </Link>
                </CardContent>
            </Card>

            <template v-else>
                <!-- Tabs -->
                <div class="flex gap-1 border-b">
                    <button
                        class="px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px"
                        :class="tab === 'profile' ? 'border-primary text-foreground' : 'border-transparent text-muted-foreground hover:text-foreground'"
                        @click="switchTab('profile')"
                    >
                        Profile
                    </button>
                    <button
                        class="px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px"
                        :class="tab === 'locations' ? 'border-primary text-foreground' : 'border-transparent text-muted-foreground hover:text-foreground'"
                        @click="switchTab('locations')"
                    >
                        Locations
                        <span v-if="locations.length" class="ml-1.5 rounded-full bg-muted px-1.5 py-0.5 text-xs">{{ locations.length }}</span>
                    </button>
                </div>

                <!-- ── PROFILE TAB ── -->
                <template v-if="tab === 'profile'">
                    <Card>
                        <CardHeader>
                            <CardTitle>Shop Details</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-5">
                            <div class="grid gap-5 sm:grid-cols-2">
                                <div class="space-y-1">
                                    <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Shop Name</p>
                                    <p class="text-sm font-medium">{{ shop.name }}</p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Status</p>
                                    <Badge :variant="statusVariant(shop.status)">
                                        {{ shop.status.charAt(0).toUpperCase() + shop.status.slice(1) }}
                                    </Badge>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Industry</p>
                                    <p class="text-sm">{{ shop.industry?.name || '—' }}</p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">EIN</p>
                                    <p class="text-sm font-mono">{{ shop.ein ? maskEin(shop.ein) : '—' }}</p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Phone</p>
                                    <p class="text-sm">{{ shop.phone || '—' }}</p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Website</p>
                                    <a
                                        v-if="shop.website"
                                        :href="shop.website"
                                        target="_blank"
                                        rel="noopener"
                                        class="text-sm text-primary hover:underline"
                                    >
                                        {{ shop.website }}
                                    </a>
                                    <p v-else class="text-sm">—</p>
                                </div>
                            </div>
                            <div v-if="shop.description" class="space-y-1">
                                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Description</p>
                                <p class="text-sm leading-relaxed">{{ shop.description }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Quick Links -->
                    <div class="grid gap-3 sm:grid-cols-2">
                        <Link href="/shop/service-requests">
                            <Card class="hover:bg-muted/50 transition-colors cursor-pointer h-full">
                                <CardContent class="py-4 flex items-center gap-3">
                                    <div class="size-9 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5 text-primary">
                                            <path fill-rule="evenodd" d="M6 3.75A2.75 2.75 0 018.75 1h2.5A2.75 2.75 0 0114 3.75v.443c.572.055 1.14.122 1.706.2C17.053 4.582 18 5.75 18 7.07v3.469c0 1.126-.694 2.191-1.83 2.54-1.952.599-4.024.921-6.17.921s-4.219-.322-6.17-.921C2.694 12.73 2 11.665 2 10.539V7.07c0-1.321.947-2.489 2.294-2.676A41.047 41.047 0 016 4.193V3.75zm6.5 0v.325a41.622 41.622 0 00-5 0V3.75c0-.69.56-1.25 1.25-1.25h2.5c.69 0 1.25.56 1.25 1.25zM10 10a1 1 0 00-1 1v.01a1 1 0 001 1h.01a1 1 0 001-1V11a1 1 0 00-1-1H10z" clip-rule="evenodd" />
                                            <path d="M3 15.055v-.684c.126.053.255.1.39.142 2.092.642 4.313.987 6.61.987 2.297 0 4.518-.345 6.61-.987.135-.041.264-.089.39-.142v.684c0 1.347-.985 2.53-2.363 2.686a41.454 41.454 0 01-9.274 0C3.985 17.585 3 16.402 3 15.055z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium">Service Requests</p>
                                        <p class="text-xs text-muted-foreground">Manage requests</p>
                                    </div>
                                </CardContent>
                            </Card>
                        </Link>
                        <Link href="/shop/bookings">
                            <Card class="hover:bg-muted/50 transition-colors cursor-pointer h-full">
                                <CardContent class="py-4 flex items-center gap-3">
                                    <div class="size-9 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5 text-primary">
                                            <path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium">Bookings</p>
                                        <p class="text-xs text-muted-foreground">View bookings</p>
                                    </div>
                                </CardContent>
                            </Card>
                        </Link>
                    </div>
                </template>

                <!-- ── LOCATIONS TAB ── -->
                <template v-if="tab === 'locations'">
                    <div class="flex justify-end">
                        <Button as="a" href="/shop/locations/create" class="w-full sm:w-auto">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5 mr-2">
                                <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                            </svg>
                            Add Location
                        </Button>
                    </div>

                    <!-- Locations list -->
                    <div v-if="locations.length > 0" class="space-y-4">
                        <Card v-for="location in locations" :key="location.id">
                            <CardHeader>
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1 space-y-2">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <CardTitle>{{ location.address_line_1 }}</CardTitle>
                                            <Badge v-if="location.is_primary" variant="default">Primary</Badge>
                                        </div>
                                        <div class="text-sm text-muted-foreground space-y-1">
                                            <p v-if="location.address_line_2">{{ location.address_line_2 }}</p>
                                            <p>{{ location.city }}, {{ location.state }} {{ location.zip_code }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-2">
                                        <Button as="a" :href="`/shop/locations/${location.id}/edit`" variant="outline" size="sm">Edit</Button>
                                        <Button
                                            v-if="!location.is_primary"
                                            variant="ghost"
                                            size="sm"
                                            :disabled="deletingLocationId === location.id"
                                            @click="handleDeleteLocation(location.id)"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5">
                                                <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z" clip-rule="evenodd" />
                                            </svg>
                                        </Button>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent class="space-y-3">
                                <div class="flex items-center gap-2">
                                    <Badge :variant="getGeocodingStatus(location).variant">
                                        {{ getGeocodingStatus(location).label }}
                                    </Badge>
                                    <span v-if="location.latitude && location.longitude" class="text-xs text-muted-foreground">
                                        {{ location.latitude.toFixed(6) }}, {{ location.longitude.toFixed(6) }}
                                    </span>
                                </div>
                                <div class="rounded-lg border bg-muted/50 p-3">
                                    <p class="text-sm font-medium mb-1">Full Address</p>
                                    <p class="text-sm text-muted-foreground">{{ formatAddress(location) }}</p>
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    Added on {{ new Date(location.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) }}
                                </p>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Empty state -->
                    <Card v-else>
                        <CardContent class="flex flex-col items-center justify-center py-12 px-4 text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-16 text-muted-foreground mb-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                            </svg>
                            <h3 class="text-lg font-semibold mb-2">No locations added yet</h3>
                            <p class="text-sm text-muted-foreground max-w-md mb-4">
                                Add your first location to start receiving service requests from providers in your area.
                            </p>
                            <Button as="a" href="/shop/locations/create">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5 mr-2">
                                    <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                                </svg>
                                Add Your First Location
                            </Button>
                        </CardContent>
                    </Card>
                </template>
            </template>
        </div>
    </AppLayout>
</template>
