<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import Icon from '@/components/ui/icon/Icon.vue';
import type * as LucideIcons from 'lucide-vue-next';

interface Props {
    label: string;
    value: string | number;
    icon?: keyof typeof LucideIcons;
    subtitle?: string;
    subtitleIcon?: keyof typeof LucideIcons;
    trend?: string;
    variant?: 'default' | 'success' | 'warning' | 'accent';
    size?: 'default' | 'sm';
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'default',
    size: 'default',
});

const cardClass = computed(() => {
    const variants = {
        default: 'border-border',
        success: 'border-green-500/20 bg-gradient-to-br from-green-50/50 to-transparent dark:from-green-950/20',
        warning: 'border-amber-500/20 bg-gradient-to-br from-amber-50/50 to-transparent dark:from-amber-950/20',
        accent: 'border-primary/30 bg-gradient-to-br from-primary/5 to-transparent',
    };
    return cn(
        'relative overflow-hidden transition-all hover:shadow-md',
        variants[props.variant],
    );
});

const trendVariant = computed(() => {
    if (!props.trend) return 'default';
    if (props.trend.startsWith('+')) return 'default';
    if (props.trend.startsWith('-')) return 'destructive';
    return 'secondary';
});

const titleClass = computed(() => {
    return props.size === 'sm' ? 'text-2xl' : 'text-3xl';
});

const gradientClass = computed(() => {
    const gradients = {
        default: 'from-muted/50 to-muted/20',
        success: 'from-green-500/30 to-green-500/10',
        warning: 'from-amber-500/30 to-amber-500/10',
        accent: 'from-primary/30 to-primary/10',
    };
    return gradients[props.variant];
});
</script>

<template>
    <Card :class="cardClass">
        <!-- Decorative gradient blob -->
        <div
            :class="cn('absolute top-0 right-0 -mt-4 -mr-4 size-24 rounded-full bg-gradient-to-br opacity-10 blur-2xl', gradientClass)"
        />

        <CardHeader :class="size === 'sm' ? 'pb-2' : 'pb-3'">
            <div class="flex items-center justify-between">
                <CardDescription class="flex items-center gap-2">
                    <Icon v-if="icon" :name="icon" size="sm" class="text-muted-foreground" />
                    {{ label }}
                </CardDescription>
                <Badge v-if="trend" :variant="trendVariant" class="font-mono text-xs">
                    {{ trend }}
                </Badge>
            </div>
            <CardTitle :class="cn('font-bold tracking-tight mt-2', titleClass)">
                <slot name="value">{{ value }}</slot>
            </CardTitle>
        </CardHeader>

        <CardContent v-if="subtitle || $slots.subtitle" :class="size === 'sm' ? 'pt-0' : 'pt-1'">
            <slot name="subtitle">
                <p class="text-sm text-muted-foreground flex items-center gap-1.5">
                    <Icon v-if="subtitleIcon" :name="subtitleIcon" size="xs" />
                    {{ subtitle }}
                </p>
            </slot>
        </CardContent>
    </Card>
</template>
