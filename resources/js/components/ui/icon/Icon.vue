<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import * as LucideIcons from 'lucide-vue-next';

interface Props {
    name: keyof typeof LucideIcons;
    class?: string;
    size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl';
}

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
});

const iconComponent = computed(() => {
    return LucideIcons[props.name];
});

const sizeClass = computed(() => {
    const sizes = {
        xs: 'size-3',
        sm: 'size-4',
        md: 'size-5',
        lg: 'size-6',
        xl: 'size-8',
    };
    return sizes[props.size];
});
</script>

<template>
    <component
        :is="iconComponent"
        :class="cn('shrink-0', sizeClass, props.class)"
    />
</template>
