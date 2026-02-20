<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { Rating, PaginatedResponse } from '@/types/marketplace';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/vue3';

interface Stats {
    average: number;
    total: number;
    breakdown: Record<number, number>;
}

interface Props {
    ratings: PaginatedResponse<Rating> | Rating[];
    stats: Stats;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Provider Profile', href: '/settings/profile?tab=provider' },
    { title: 'My Ratings', href: '/provider/ratings' },
];

const isPaginated = (r: any): r is PaginatedResponse<Rating> => r && 'data' in r;

const ratingsList = isPaginated(props.ratings) ? props.ratings.data : (props.ratings as Rating[]);
const hasPagination = isPaginated(props.ratings);

const formatDate = (date: string) =>
    new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(date));

const starPercent = (star: number) => {
    if (!props.stats.total) return 0;
    return Math.round(((props.stats.breakdown[star] ?? 0) / props.stats.total) * 100);
};

const loadMore = () => {
    if (hasPagination && props.ratings.current_page < props.ratings.last_page) {
        router.get('/provider/ratings', { page: props.ratings.current_page + 1 }, { preserveState: true, preserveScroll: true });
    }
};

const renderStars = (rating: number) => '★'.repeat(rating) + '☆'.repeat(5 - rating);
</script>

<template>
    <Head title="My Ratings" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6 max-w-4xl">
            <div class="space-y-2">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">Ratings & Reviews</h1>
                <p class="text-sm text-muted-foreground">See what coffee shops are saying about you</p>
            </div>

            <!-- Summary Cards -->
                <div class="grid gap-4 md:grid-cols-2">
                    <!-- Overall Rating -->
                    <Card>
                        <CardHeader>
                            <CardDescription>Overall Rating</CardDescription>
                            <div class="flex items-baseline gap-2">
                                <span class="text-4xl font-bold">{{ Number(stats.average).toFixed(1) }}</span>
                                <span class="text-2xl text-yellow-500">★</span>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <p class="text-sm text-muted-foreground">Based on {{ stats.total }} review{{ stats.total !== 1 ? 's' : '' }}</p>
                        </CardContent>
                    </Card>

                    <!-- Star Breakdown -->
                    <Card>
                        <CardHeader>
                            <CardDescription>Rating Breakdown</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-2">
                            <div v-for="star in [5, 4, 3, 2, 1]" :key="star" class="flex items-center gap-2 text-sm">
                                <span class="w-4 text-right">{{ star }}</span>
                                <span class="text-yellow-500 text-xs">★</span>
                                <div class="flex-1 rounded-full bg-muted h-2 overflow-hidden">
                                    <div
                                        class="h-full rounded-full bg-yellow-400 transition-all"
                                        :style="{ width: `${starPercent(star)}%` }"
                                    />
                                </div>
                                <span class="w-8 text-right text-muted-foreground">{{ stats.breakdown[star] ?? 0 }}</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Reviews List -->
                <Card>
                    <CardHeader>
                        <CardTitle>Recent Reviews</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="ratingsList.length === 0" class="py-12 text-center text-muted-foreground">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-12 mx-auto mb-3 opacity-40">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                            </svg>
                            <p>No reviews yet. Complete bookings to receive ratings.</p>
                        </div>

                        <div v-else class="divide-y">
                            <div v-for="rating in ratingsList" :key="rating.id" class="py-4 first:pt-0 last:pb-0">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1 space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-yellow-500 tracking-wider">{{ renderStars(rating.rating) }}</span>
                                            <span class="text-sm font-semibold">{{ rating.rating }}/5</span>
                                        </div>
                                        <p class="text-sm font-medium">
                                            {{ rating.booking?.service_request?.shop_location?.shop?.name ?? 'Coffee Shop' }}
                                        </p>
                                        <p v-if="rating.comment" class="text-sm text-muted-foreground">{{ rating.comment }}</p>
                                        <p v-else class="text-sm text-muted-foreground italic">No comment left</p>
                                    </div>
                                    <span class="text-xs text-muted-foreground whitespace-nowrap">{{ formatDate(rating.created_at) }}</span>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Load More -->
                <div v-if="hasPagination && (ratings as PaginatedResponse<Rating>).current_page < (ratings as PaginatedResponse<Rating>).last_page" class="flex justify-center">
                    <Button variant="outline" @click="loadMore">Load More Reviews</Button>
                </div>
        </div>
    </AppLayout>
</template>
