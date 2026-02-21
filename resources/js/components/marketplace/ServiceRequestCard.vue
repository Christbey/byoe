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
    try {
        const dateStr = props.serviceRequest.service_date;
        // Extract just the date part if it's a full datetime string
        const datePart = dateStr.split('T')[0];
        const [year, month, day] = datePart.split('-').map(Number);
        const date = new Date(year, month - 1, day);

        if (isNaN(date.getTime())) {
            return dateStr; // Fallback to original string
        }

        return date.toLocaleDateString('en-US', {
            weekday: 'short',
            month: 'short',
            day: 'numeric',
        });
    } catch (error) {
        return props.serviceRequest.service_date;
    }
});

const formatTime = (time: string) => {
    if (!time) return '';
    // Handle plain time strings like "08:00:00" or "08:00"
    const [hours, minutes] = time.split(':').map(Number);
    const period = hours >= 12 ? 'PM' : 'AM';
    const displayHours = hours % 12 || 12;
    return `${displayHours}:${minutes.toString().padStart(2, '0')} ${period}`;
};

const formattedTime = computed(() => {
    const start = formatTime(props.serviceRequest.start_time);
    const end = formatTime(props.serviceRequest.end_time);
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
