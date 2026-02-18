<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import Button from '@/components/ui/button/Button.vue';

interface Props {
    variant?: 'primary' | 'secondary' | 'danger';
    disabled?: boolean;
    loading?: boolean;
    class?: HTMLAttributes['class'];
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'primary',
    disabled: false,
    loading: false,
});

const buttonVariant = computed(() => {
    switch (props.variant) {
        case 'primary':
            return 'default';
        case 'secondary':
            return 'secondary';
        case 'danger':
            return 'destructive';
        default:
            return 'default';
    }
});

const isDisabled = computed(() => props.disabled || props.loading);
</script>

<template>
    <Button
        :variant="buttonVariant"
        :disabled="isDisabled"
        :class="
            cn(
                'w-full min-h-[48px] h-auto py-3 px-4 text-base font-semibold touch-manipulation select-none',
                'active:scale-[0.98] transition-transform duration-100',
                'focus-visible:ring-4',
                props.class,
            )
        "
    >
        <span v-if="loading" class="flex items-center gap-2">
            <svg
                class="animate-spin size-5"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
            >
                <circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                ></circle>
                <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                ></path>
            </svg>
            <slot name="loading">Loading...</slot>
        </span>
        <slot v-else />
    </Button>
</template>
