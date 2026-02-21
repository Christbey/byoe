<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';

interface Props {
    status?: 'default' | 'success' | 'warning' | 'danger' | 'pending';
    clickable?: boolean;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    status: 'default',
    clickable: false,
});

const emit = defineEmits<{
    click: [];
}>();

const handleClick = () => {
    if (props.clickable) {
        emit('click');
    }
};

const accentClass = computed(() => {
    const accents = {
        default: 'bg-muted group-hover:bg-muted-foreground/20',
        success: 'bg-green-500 group-hover:bg-green-500',
        warning: 'bg-amber-500 group-hover:bg-amber-500',
        danger: 'bg-red-500 group-hover:bg-red-500',
        pending: 'bg-blue-500 group-hover:bg-blue-500',
    };
    return accents[props.status];
});
</script>

<template>
    <div
        :class="cn(
            'group relative p-4 border rounded-lg transition-all',
            'hover:border-primary/50 hover:bg-accent/5 hover:shadow-sm',
            clickable && 'cursor-pointer',
            props.class,
        )"
        @click="handleClick"
    >
        <!-- Left accent bar -->
        <div
            :class="cn(
                'absolute left-0 top-0 bottom-0 w-1 rounded-l-lg transition-all',
                accentClass,
            )"
        />

        <div class="flex items-start justify-between gap-4">
            <div class="flex-1 min-w-0 space-y-1 pl-3">
                <slot name="header" />
                <slot name="content" />
                <slot name="footer" />
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <slot name="badge" />
                <slot name="action" />
            </div>
        </div>
    </div>
</template>
