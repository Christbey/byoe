<script setup lang="ts">
import Icon from '@/components/ui/icon/Icon.vue';
import { Button } from '@/components/ui/button';
import type * as LucideIcons from 'lucide-vue-next';

interface Props {
    icon: keyof typeof LucideIcons;
    title: string;
    description: string;
    actionLabel?: string;
    actionHref?: string;
}

const props = defineProps<Props>();
</script>

<template>
    <div class="flex flex-col items-center justify-center py-12 px-4 text-center">
        <!-- Icon container -->
        <div class="relative mb-6">
            <div class="absolute inset-0 rounded-full bg-primary/10 blur-xl" />
            <div class="relative rounded-full bg-muted/50 p-6 backdrop-blur-sm">
                <Icon :name="icon" class="size-12 text-muted-foreground" />
            </div>
        </div>

        <!-- Content -->
        <h3 class="text-lg font-semibold mb-2">{{ title }}</h3>
        <p class="text-sm text-muted-foreground max-w-sm mb-6">
            {{ description }}
        </p>

        <!-- Action slot or default button -->
        <slot name="action">
            <Button v-if="actionLabel && actionHref" as="a" :href="actionHref">
                <Icon name="Plus" size="sm" class="mr-2" />
                {{ actionLabel }}
            </Button>
        </slot>
    </div>
</template>
