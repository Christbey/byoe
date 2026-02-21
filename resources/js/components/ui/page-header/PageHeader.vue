<script setup lang="ts">
import type { BreadcrumbItem } from '@/types';
import {
    Breadcrumb,
    BreadcrumbItem as BreadcrumbItemComponent,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';

interface Props {
    title: string;
    description?: string;
    breadcrumbs?: BreadcrumbItem[];
}

const props = defineProps<Props>();
</script>

<template>
    <div class="space-y-3">
        <!-- Breadcrumbs -->
        <Breadcrumb v-if="breadcrumbs && breadcrumbs.length > 0">
            <BreadcrumbList>
                <template v-for="(item, index) in breadcrumbs" :key="index">
                    <BreadcrumbItemComponent>
                        <BreadcrumbLink v-if="index < breadcrumbs.length - 1" :href="item.href">
                            {{ item.title }}
                        </BreadcrumbLink>
                        <BreadcrumbPage v-else>
                            {{ item.title }}
                        </BreadcrumbPage>
                    </BreadcrumbItemComponent>
                    <BreadcrumbSeparator v-if="index < breadcrumbs.length - 1" />
                </template>
            </BreadcrumbList>
        </Breadcrumb>

        <!-- Header content -->
        <div class="flex items-start justify-between gap-4">
            <div class="space-y-1.5 flex-1">
                <h1 class="text-3xl font-bold tracking-tight">{{ title }}</h1>
                <p v-if="description || $slots.description" class="text-muted-foreground max-w-2xl">
                    <slot name="description">{{ description }}</slot>
                </p>
            </div>
            <div v-if="$slots.action" class="shrink-0">
                <slot name="action" />
            </div>
        </div>

        <!-- Optional help/info section -->
        <slot name="help" />
    </div>
</template>
