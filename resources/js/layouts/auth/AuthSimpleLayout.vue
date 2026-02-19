<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex min-h-svh flex-col bg-background">
        <div class="flex flex-1 items-center justify-center p-6 md:p-10">
            <div class="w-full max-w-sm">
                <div class="flex flex-col gap-8">
                    <div class="flex flex-col items-center gap-4">
                        <Link
                            :href="home()"
                            class="flex flex-col items-center gap-2 font-medium"
                        >
                            <div class="mb-1 flex h-9 w-9 items-center justify-center rounded-md">
                                <AppLogoIcon
                                    class="size-9 fill-current text-[var(--foreground)] dark:text-white"
                                />
                            </div>
                            <span class="sr-only">{{ title }}</span>
                        </Link>
                        <div class="space-y-2 text-center">
                            <h1 class="text-xl font-medium">{{ title }}</h1>
                            <p class="text-center text-sm text-muted-foreground">
                                {{ description }}
                            </p>
                        </div>
                    </div>
                    <slot />
                </div>
            </div>
        </div>

        <footer class="border-t px-6 py-4">
            <nav class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1">
                <span class="text-xs text-muted-foreground">&copy; {{ new Date().getFullYear() }} BYOE</span>
                <a href="/legal/terms" class="text-xs text-muted-foreground transition-colors hover:text-foreground">Terms of Service</a>
                <a href="/legal/privacy" class="text-xs text-muted-foreground transition-colors hover:text-foreground">Privacy Policy</a>
                <a href="/legal/cookies" class="text-xs text-muted-foreground transition-colors hover:text-foreground">Cookie Policy</a>
                <a href="/legal/contractor" class="text-xs text-muted-foreground transition-colors hover:text-foreground">Contractor Agreement</a>
            </nav>
        </footer>
    </div>
</template>
