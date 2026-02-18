<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { Shop } from '@/types/marketplace';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';

interface Props {
    shop: Shop;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Shop Profile', href: '/shop/profile' },
];

const maskEin = (ein: string) => {
    const digits = ein.replace('-', '');
    return digits.slice(0, 2) + '-***' + digits.slice(5);
};

const statusVariant = (status: string) => {
    const map: Record<string, string> = { active: 'default', inactive: 'secondary', suspended: 'destructive' };
    return map[status] || 'outline';
};
</script>

<template>
    <Head title="Shop Profile" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6 max-w-3xl">
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
                    <p class="text-sm text-muted-foreground">Manage your shop information and settings</p>
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
                <!-- Details Card -->
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
                <div class="grid gap-3 sm:grid-cols-3">
                    <Link href="/shop/locations">
                        <Card class="hover:bg-muted/50 transition-colors cursor-pointer h-full">
                            <CardContent class="py-4 flex items-center gap-3">
                                <div class="size-9 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5 text-primary">
                                        <path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 103 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 002.273 1.765 11.842 11.842 0 00.976.544l.062.029.018.008.006.003zM10 11.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium">Locations</p>
                                    <p class="text-xs text-muted-foreground">Manage addresses</p>
                                </div>
                            </CardContent>
                        </Card>
                    </Link>
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
                                    <p class="text-sm font-medium">Requests</p>
                                    <p class="text-xs text-muted-foreground">Service requests</p>
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
        </div>
    </AppLayout>
</template>
