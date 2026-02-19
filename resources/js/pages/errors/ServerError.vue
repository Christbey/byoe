<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { home } from '@/routes';
</script>

<template>
    <Head title="Server Error" />

    <div class="flex min-h-svh flex-col items-center justify-center bg-background p-6 text-center">
        <p class="text-sm font-semibold uppercase tracking-widest text-muted-foreground">500</p>
        <h1 class="mt-3 text-3xl font-bold tracking-tight">Something went wrong</h1>
        <p class="mt-3 max-w-sm text-muted-foreground">
            We're having trouble on our end. Our team has been notified. Please try again in a moment.
        </p>
        <div class="mt-8">
            <Link
                :href="home()"
                class="inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90"
            >
                Back to home
            </Link>
        </div>
    </div>
</template>
