<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

defineProps<{
    title: string;
    effectiveDate: string;
}>();
</script>

<template>
    <div class="min-h-svh bg-background text-foreground">
        <!-- Top bar -->
        <header class="border-b">
            <div
                class="mx-auto flex max-w-4xl items-center justify-between px-6 py-4"
            >
                <Link href="/" class="flex items-center gap-2">
                    <AppLogoIcon class="size-7 fill-current" />
                    <span class="font-semibold">ShiftFinder</span>
                </Link>
                <nav class="flex gap-4 text-sm text-muted-foreground">
                    <Link
                        href="/legal/terms"
                        class="transition-colors hover:text-foreground"
                        >Terms</Link
                    >
                    <Link
                        href="/legal/privacy"
                        class="transition-colors hover:text-foreground"
                        >Privacy</Link
                    >
                    <Link
                        href="/legal/cookies"
                        class="transition-colors hover:text-foreground"
                        >Cookies</Link
                    >
                    <Link
                        href="/legal/contractor"
                        class="transition-colors hover:text-foreground"
                        >Contractor</Link
                    >
                </nav>
            </div>
        </header>

        <!-- Document -->
        <main class="mx-auto max-w-4xl px-6 py-12">
            <div class="mb-10 space-y-2">
                <h1 class="text-3xl font-bold tracking-tight">{{ title }}</h1>
                <p class="text-sm text-muted-foreground">
                    Effective date: {{ effectiveDate }}
                </p>
            </div>

            <div class="legal-content">
                <slot />
            </div>
        </main>

        <!-- Footer -->
        <footer class="mt-12 border-t">
            <div
                class="mx-auto flex max-w-4xl flex-wrap items-center justify-between gap-4 px-6 py-6 text-xs text-muted-foreground"
            >
                <span
                    >&copy; {{ new Date().getFullYear() }} ShiftFinder. All
                    rights reserved.</span
                >
                <nav class="flex gap-4">
                    <Link
                        href="/legal/terms"
                        class="transition-colors hover:text-foreground"
                        >Terms</Link
                    >
                    <Link
                        href="/legal/privacy"
                        class="transition-colors hover:text-foreground"
                        >Privacy</Link
                    >
                    <Link
                        href="/legal/cookies"
                        class="transition-colors hover:text-foreground"
                        >Cookies</Link
                    >
                    <Link
                        href="/legal/contractor"
                        class="transition-colors hover:text-foreground"
                        >Contractor</Link
                    >
                </nav>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.legal-content :deep(h2) {
    font-size: 1.125rem;
    font-weight: 600;
    letter-spacing: -0.015em;
    margin-top: 2.5rem;
    margin-bottom: 0.75rem;
    color: var(--foreground);
}

.legal-content :deep(h3) {
    font-size: 0.9375rem;
    font-weight: 600;
    margin-top: 1.5rem;
    margin-bottom: 0.5rem;
    color: var(--foreground);
}

.legal-content :deep(p) {
    margin-bottom: 1rem;
    line-height: 1.75;
    color: var(--muted-foreground);
}

.legal-content :deep(ul) {
    margin-bottom: 1rem;
    padding-left: 1.5rem;
    list-style-type: disc;
}

.legal-content :deep(li) {
    margin-bottom: 0.375rem;
    line-height: 1.75;
    color: var(--muted-foreground);
}

.legal-content :deep(a) {
    color: var(--primary);
    text-decoration: none;
}

.legal-content :deep(a:hover) {
    text-decoration: underline;
}

.legal-content :deep(strong) {
    color: var(--foreground);
    font-weight: 600;
}
</style>
