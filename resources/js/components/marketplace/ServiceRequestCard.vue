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
import Icon from '@/components/ui/icon/Icon.vue';
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
    <Card :class="cn('w-full transition-all hover:shadow-md hover:border-primary/50', props.class)">
        <CardHeader>
            <div class="flex flex-col gap-2">
                <div class="flex items-start justify-between gap-2">
                    <CardTitle class="text-lg">
                        {{ serviceRequest.title }}
                    </CardTitle>
                    <div class="flex items-center gap-1 shrink-0 font-bold text-lg text-primary">
                        <Icon name="DollarSign" size="sm" />
                        <span>{{ formattedPrice.replace('$', '') }}</span>
                    </div>
                </div>
                <CardDescription class="flex items-center gap-1.5">
                    <Icon name="Store" size="xs" />
                    {{ shopName }}
                </CardDescription>
            </div>
        </CardHeader>

        <CardContent class="space-y-4">
            <p class="text-sm text-muted-foreground line-clamp-3">
                {{ serviceRequest.description }}
            </p>

            <div class="flex flex-col gap-2 text-sm">
                <div class="flex items-center gap-2 text-muted-foreground">
                    <Icon name="Calendar" size="xs" />
                    <span>{{ formattedDate }}</span>
                </div>
                <div class="flex items-center gap-2 text-muted-foreground">
                    <Icon name="Clock" size="xs" />
                    <span>{{ formattedTime }}</span>
                </div>
                <div class="flex items-center gap-2 text-muted-foreground">
                    <Icon name="MapPin" size="xs" />
                    <span>{{ locationText }}</span>
                    <span v-if="distance != null" class="text-xs">
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
