<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardFooter from '@/components/ui/card/CardFooter.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import type { ServiceRequest } from '@/types/marketplace';

interface Props {
    serviceRequest: ServiceRequest;
    class?: HTMLAttributes['class'];
    distance?: number;
}

const props = defineProps<Props>();

const formattedDate = computed(() => {
    const date = new Date(props.serviceRequest.service_date);
    return date.toLocaleDateString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
    });
});

const formattedTime = computed(() => {
    const opts: Intl.DateTimeFormatOptions = { hour: 'numeric', minute: '2-digit' };
    const start = new Date(props.serviceRequest.start_time).toLocaleTimeString('en-US', opts);
    const end = new Date(props.serviceRequest.end_time).toLocaleTimeString('en-US', opts);
    return `${start} - ${end}`;
});

const formattedPrice = computed(() => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(props.serviceRequest.price);
});

const locationText = computed(() => {
    const location = props.serviceRequest.shop_location;
    if (!location) return 'Location not specified';
    return `${location.city}, ${location.state}`;
});

const shopName = computed(() => {
    return props.serviceRequest.shop_location?.shop?.name || 'Unknown Shop';
});
</script>

<template>
    <Card :class="cn('w-full', props.class)">
        <CardHeader>
            <div class="flex flex-col gap-2">
                <div class="flex items-start justify-between gap-2">
                    <CardTitle class="text-lg">
                        {{ serviceRequest.title }}
                    </CardTitle>
                    <Badge variant="secondary" class="shrink-0">
                        {{ formattedPrice }}
                    </Badge>
                </div>
                <CardDescription class="text-sm">
                    {{ shopName }}
                </CardDescription>
            </div>
        </CardHeader>

        <CardContent class="space-y-4">
            <p class="text-sm text-muted-foreground line-clamp-3">
                {{ serviceRequest.description }}
            </p>

            <div class="flex flex-col gap-2 text-sm">
                <div class="flex items-center gap-2">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                        class="size-4 text-muted-foreground"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    <span>{{ formattedDate }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                        class="size-4 text-muted-foreground"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    <span>{{ formattedTime }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                        class="size-4 text-muted-foreground"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 103 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 002.273 1.765 11.842 11.842 0 00.976.544l.062.029.018.008.006.003zM10 11.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    <span>{{ locationText }}</span>
                    <span v-if="distance != null" class="text-xs text-muted-foreground">
                        · ~{{ Math.round(distance) }} mi
                    </span>
                </div>
            </div>

            <div
                v-if="
                    serviceRequest.skills_required &&
                    serviceRequest.skills_required.length > 0
                "
                class="flex flex-wrap gap-2"
            >
                <Badge
                    v-for="skill in serviceRequest.skills_required"
                    :key="skill"
                    variant="outline"
                    class="text-xs"
                >
                    {{ skill }}
                </Badge>
            </div>
        </CardContent>

        <CardFooter v-if="$slots.default" class="pt-0">
            <slot />
        </CardFooter>
    </Card>
</template>
