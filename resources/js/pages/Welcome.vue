<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, login } from '@/routes';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);

const featurePills = [
    'Fast booking',
    'Stripe payouts',
    'Clear status tracking',
];

const quickStats = [
    { label: 'Booking flow', value: '3 taps' },
    { label: 'Contractor payout', value: 'Automatic' },
    { label: 'Coverage style', value: 'On demand' },
];

const businessSteps = [
    'Post a shift with a fixed rate and timing.',
    'Get matched with an available contractor.',
    'Complete the shift and release payment automatically.',
];

const providerSteps = [
    'Browse jobs that fit your schedule.',
    'Accept work without chasing invoices.',
    'Track bookings, reviews, and payouts in one place.',
];
</script>

<template>
    <Head title="ShiftFinder" />

    <div class="min-h-screen px-4 py-4 text-foreground md:px-6 md:py-6">
        <div class="mx-auto flex min-h-[calc(100vh-2rem)] max-w-7xl flex-col overflow-hidden rounded-[36px] border border-white/45 bg-white/52 shadow-[0_28px_80px_-42px_rgba(15,23,42,0.36)] backdrop-blur-2xl dark:border-white/8 dark:bg-slate-950/38">
            <header class="border-b border-white/45 px-6 py-5 dark:border-white/8 md:px-8">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="flex size-11 items-center justify-center rounded-[20px] bg-[linear-gradient(180deg,hsl(206_100%_64%),hsl(217_100%_54%))] shadow-[0_14px_28px_-16px_rgba(14,110,255,0.75)]">
                            <span class="text-base font-semibold text-white">SF</span>
                        </div>
                        <div>
                            <p class="text-[0.72rem] font-semibold uppercase tracking-[0.28em] text-muted-foreground">
                                ShiftFinder
                            </p>
                            <p class="text-sm font-medium tracking-[-0.02em]">
                                Staffing, cleaned up.
                            </p>
                        </div>
                    </div>

                    <nav class="flex items-center gap-2">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="dashboard()"
                            class="inline-flex h-11 items-center rounded-full border border-white/50 bg-white/76 px-5 text-sm font-medium shadow-[0_10px_28px_-20px_rgba(15,23,42,0.3)] backdrop-blur-md transition hover:bg-white/92 dark:border-white/8 dark:bg-white/8 dark:hover:bg-white/12"
                        >
                            Open dashboard
                        </Link>
                        <template v-else>
                            <Link
                                :href="login()"
                                class="inline-flex h-11 items-center rounded-full px-4 text-sm font-medium text-muted-foreground transition hover:text-foreground"
                            >
                                Log in
                            </Link>
                            <Link
                                v-if="canRegister"
                                href="/register"
                                class="inline-flex h-11 items-center rounded-full bg-primary px-5 text-sm font-medium text-primary-foreground shadow-[0_12px_28px_-14px_rgba(14,110,255,0.68)] transition hover:bg-primary/92"
                            >
                                Create account
                            </Link>
                        </template>
                    </nav>
                </div>
            </header>

            <main class="flex flex-1 flex-col">
                <section class="relative overflow-hidden px-6 py-10 md:px-8 md:py-12">
                    <div class="absolute inset-x-0 top-0 h-64 bg-[radial-gradient(circle_at_top,_rgba(97,182,255,0.28),_transparent_54%)]" />

                    <div class="relative grid gap-8 lg:grid-cols-[1.15fr_0.85fr] lg:items-center">
                        <div class="space-y-7">
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-for="pill in featurePills"
                                    :key="pill"
                                    class="rounded-full border border-white/55 bg-white/72 px-3 py-1 text-xs font-medium text-muted-foreground shadow-[0_8px_20px_-18px_rgba(15,23,42,0.35)] backdrop-blur-md dark:border-white/10 dark:bg-white/8"
                                >
                                    {{ pill }}
                                </span>
                            </div>

                            <div class="max-w-3xl space-y-4">
                                <h1 class="text-4xl font-semibold tracking-[-0.06em] text-foreground md:text-6xl">
                                    Flexible staffing with a calmer, cleaner workflow.
                                </h1>
                                <p class="max-w-2xl text-base leading-7 text-muted-foreground md:text-lg">
                                    ShiftFinder helps local businesses fill service gaps fast while contractors manage work, bookings, and payouts from one polished dashboard.
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-3">
                                <template v-if="canRegister && !$page.props.auth.user">
                                    <Link
                                        href="/register?role=shop_owner"
                                        class="inline-flex h-12 items-center rounded-full bg-primary px-6 text-sm font-medium text-primary-foreground shadow-[0_14px_32px_-16px_rgba(14,110,255,0.7)] transition hover:bg-primary/92"
                                    >
                                        I need staff
                                    </Link>
                                    <Link
                                        href="/register?role=provider"
                                        class="inline-flex h-12 items-center rounded-full border border-white/55 bg-white/78 px-6 text-sm font-medium shadow-[0_10px_28px_-20px_rgba(15,23,42,0.32)] backdrop-blur-md transition hover:bg-white/92 dark:border-white/10 dark:bg-white/8 dark:hover:bg-white/12"
                                    >
                                        I want to work
                                    </Link>
                                </template>
                                <Link
                                    v-else-if="$page.props.auth.user"
                                    :href="dashboard()"
                                    class="inline-flex h-12 items-center rounded-full bg-primary px-6 text-sm font-medium text-primary-foreground shadow-[0_14px_32px_-16px_rgba(14,110,255,0.7)] transition hover:bg-primary/92"
                                >
                                    Continue to dashboard
                                </Link>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-3">
                                <div
                                    v-for="stat in quickStats"
                                    :key="stat.label"
                                    class="ios-panel px-4 py-4"
                                >
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-foreground">
                                        {{ stat.label }}
                                    </p>
                                    <p class="mt-2 text-xl font-semibold tracking-[-0.03em]">
                                        {{ stat.value }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="ios-surface relative overflow-hidden p-5 md:p-6">
                            <div class="absolute inset-x-6 top-0 h-24 rounded-b-[40px] bg-[radial-gradient(circle_at_center,_rgba(97,182,255,0.28),_transparent_70%)]" />
                            <div class="relative space-y-5">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-muted-foreground">
                                            Live snapshot
                                        </p>
                                        <h2 class="mt-2 text-2xl font-semibold tracking-[-0.04em]">
                                            Swift, readable operations.
                                        </h2>
                                    </div>
                                    <span class="rounded-full bg-emerald-500/14 px-3 py-1 text-xs font-semibold text-emerald-700 dark:text-emerald-300">
                                        Active
                                    </span>
                                </div>

                                <div class="space-y-3">
                                    <div class="ios-panel p-4">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-sm font-medium">Morning espresso coverage</p>
                                                <p class="mt-1 text-sm text-muted-foreground">Tomorrow, 7:00 AM to 1:00 PM</p>
                                            </div>
                                            <span class="text-lg font-semibold tracking-[-0.03em]">$180</span>
                                        </div>
                                    </div>
                                    <div class="ios-panel p-4">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-sm font-medium">Assigned contractor</p>
                                                <p class="mt-1 text-sm text-muted-foreground">Stripe-ready, rating 4.9</p>
                                            </div>
                                            <span class="rounded-full bg-primary/12 px-3 py-1 text-xs font-semibold text-primary">
                                                Confirmed
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ios-panel p-4">
                                        <p class="text-sm font-medium">Timeline</p>
                                        <div class="mt-3 flex items-center gap-2">
                                            <div class="h-2 flex-1 rounded-full bg-primary" />
                                            <div class="h-2 flex-1 rounded-full bg-primary/35" />
                                            <div class="h-2 flex-1 rounded-full bg-muted" />
                                        </div>
                                        <div class="mt-2 flex justify-between text-xs text-muted-foreground">
                                            <span>Posted</span>
                                            <span>Booked</span>
                                            <span>Paid</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="grid gap-4 border-t border-white/45 px-6 py-8 dark:border-white/8 md:px-8 lg:grid-cols-2">
                    <div class="ios-panel p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-foreground">
                            For businesses
                        </p>
                        <h3 class="mt-3 text-2xl font-semibold tracking-[-0.04em]">
                            Staff gaps handled without operational clutter.
                        </h3>
                        <div class="mt-5 space-y-4">
                            <div
                                v-for="(step, index) in businessSteps"
                                :key="step"
                                class="flex items-start gap-3"
                            >
                                <span class="flex size-8 items-center justify-center rounded-full bg-primary/12 text-sm font-semibold text-primary">
                                    {{ index + 1 }}
                                </span>
                                <p class="pt-1 text-sm leading-6 text-muted-foreground">
                                    {{ step }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="ios-panel p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-foreground">
                            For contractors
                        </p>
                        <h3 class="mt-3 text-2xl font-semibold tracking-[-0.04em]">
                            Work discovery with less friction and better visibility.
                        </h3>
                        <div class="mt-5 space-y-4">
                            <div
                                v-for="(step, index) in providerSteps"
                                :key="step"
                                class="flex items-start gap-3"
                            >
                                <span class="flex size-8 items-center justify-center rounded-full bg-white/80 text-sm font-semibold text-foreground shadow-[0_10px_18px_-14px_rgba(15,23,42,0.35)] dark:bg-white/10">
                                    {{ index + 1 }}
                                </span>
                                <p class="pt-1 text-sm leading-6 text-muted-foreground">
                                    {{ step }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </div>
</template>
