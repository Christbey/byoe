<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<template>
    <div
        class="flex aspect-square size-9 items-center justify-center rounded-[18px] bg-[linear-gradient(180deg,hsl(206_100%_64%),hsl(217_100%_54%))] text-sidebar-primary-foreground shadow-[0_14px_28px_-16px_rgba(14,110,255,0.85)]"
    >
        <AppLogoIcon class="size-5 fill-current text-white" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold tracking-[-0.02em]"
            >ShiftFinder</span
        >
    </div>
</template>
