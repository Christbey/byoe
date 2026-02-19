<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { home } from '@/routes';
</script>

<template>
    <Head title="Page Not Found" />

    <div class="flex min-h-svh flex-col items-center justify-center bg-background p-6 text-center">
        <p class="text-sm font-semibold uppercase tracking-widest text-muted-foreground">404</p>
        <h1 class="mt-3 text-3xl font-bold tracking-tight">Page not found</h1>
        <p class="mt-3 max-w-sm text-muted-foreground">
            Sorry, we couldn't find the page you're looking for. It may have moved or no longer exists.
        </p>
        <div class="mt-8 flex gap-4">
            <Link
                :href="home()"
                class="inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90"
            >
                Back to home
            </Link>
            <button
                type="button"
                class="inline-flex h-9 items-center justify-center rounded-md border border-input bg-background px-4 text-sm font-medium hover:bg-accent hover:text-accent-foreground"
                @click="() => history.back()"
            >
                Go back
            </button>
        </div>
    </div>
</template>
