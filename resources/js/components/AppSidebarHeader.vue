<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);
</script>

<template>
    <header
        class="sticky top-0 z-20 mx-3 mt-3 flex h-16 shrink-0 items-center gap-2 rounded-[24px] border border-white/45 bg-white/72 px-5 shadow-[0_16px_38px_-26px_rgba(15,23,42,0.34)] backdrop-blur-xl transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 dark:border-white/8 dark:bg-white/6 md:mx-4 md:px-5"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1 rounded-full" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
    </header>
</template>
