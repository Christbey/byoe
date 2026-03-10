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
        <div class="flex flex-1 items-center justify-center p-4 md:p-8">
            <div class="ios-surface w-full max-w-md px-6 py-8 md:px-8">
                <div class="flex flex-col gap-8">
                    <div class="flex flex-col items-center gap-5">
                        <Link
                            :href="home()"
                            class="flex flex-col items-center gap-2 font-medium"
                        >
                            <div
                                class="mb-1 flex h-12 w-12 items-center justify-center rounded-[20px] bg-[linear-gradient(180deg,hsl(206_100%_64%),hsl(217_100%_54%))] shadow-[0_16px_32px_-18px_rgba(14,110,255,0.8)]"
                            >
                                <AppLogoIcon
                                    class="size-7 fill-current text-white"
                                />
                            </div>
                            <span class="sr-only">{{ title }}</span>
                        </Link>
                        <div class="space-y-2 text-center">
                            <h1 class="text-2xl font-semibold tracking-[-0.04em]">{{ title }}</h1>
                            <p
                                class="text-center text-sm leading-6 text-muted-foreground"
                            >
                                {{ description }}
                            </p>
                        </div>
                    </div>
                    <slot />
                </div>
            </div>
        </div>

        <footer class="px-6 py-5">
            <nav
                class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1"
            >
                <span class="text-xs text-muted-foreground"
                    >&copy; {{ new Date().getFullYear() }} ShiftFinder</span
                >
                <a
                    href="/legal/terms"
                    class="text-xs text-muted-foreground transition-colors hover:text-foreground"
                    >Terms of Service</a
                >
                <a
                    href="/legal/privacy"
                    class="text-xs text-muted-foreground transition-colors hover:text-foreground"
                    >Privacy Policy</a
                >
                <a
                    href="/legal/cookies"
                    class="text-xs text-muted-foreground transition-colors hover:text-foreground"
                    >Cookie Policy</a
                >
                <a
                    href="/legal/contractor"
                    class="text-xs text-muted-foreground transition-colors hover:text-foreground"
                    >Contractor Agreement</a
                >
            </nav>
        </footer>
    </div>
</template>
