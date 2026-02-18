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
</script>

<template>
    <Head title="Bring Your Own Expertise — On-Demand Staffing for Local Businesses" />

    <div class="min-h-screen bg-background text-foreground">
        <!-- Nav -->
        <header class="border-b">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
                <span class="text-lg font-bold tracking-tight">byoe</span>
                <nav class="flex items-center gap-3">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="rounded-md border px-4 py-1.5 text-sm font-medium transition-colors hover:bg-muted"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="text-sm font-medium text-muted-foreground hover:text-foreground"
                        >
                            Log in
                        </Link>
                        <Link
                            v-if="canRegister"
                            href="/register"
                            class="rounded-md bg-primary px-4 py-1.5 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
                        >
                            Get started
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Hero -->
        <section class="mx-auto max-w-5xl px-6 py-20 text-center">
            <h1 class="text-4xl font-bold tracking-tight md:text-5xl lg:text-6xl">
                On-demand staffing.<br />
                <span class="text-primary">No agencies. No overhead.</span>
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg text-muted-foreground">
                Connect your business with skilled independent contractors, or pick up flexible shifts and get paid directly — all in one platform.
            </p>

            <!-- Dual CTA cards -->
            <div v-if="canRegister && !$page.props.auth.user" class="mt-12 grid gap-6 md:grid-cols-2 max-w-2xl mx-auto">
                <Link href="/register?role=shop_owner" class="group block">
                    <div class="rounded-xl border-2 p-8 text-left transition-all hover:border-primary hover:shadow-md">
                        <div class="mb-4 text-4xl">🏪</div>
                        <h2 class="text-xl font-bold">I need staff</h2>
                        <p class="mt-2 text-sm text-muted-foreground leading-relaxed">
                            Post shifts, set your rate, and get matched with vetted contractors — no long-term commitments.
                        </p>
                        <div class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-primary">
                            Register as a shop
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4 transition-transform group-hover:translate-x-1">
                                <path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </Link>

                <Link href="/register?role=provider" class="group block">
                    <div class="rounded-xl border-2 p-8 text-left transition-all hover:border-primary hover:shadow-md">
                        <div class="mb-4 text-4xl">💼</div>
                        <h2 class="text-xl font-bold">I want to work</h2>
                        <p class="mt-2 text-sm text-muted-foreground leading-relaxed">
                            Browse available shifts near you, set your own schedule, and get paid directly — no middleman.
                        </p>
                        <div class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-primary">
                            Register as a contractor
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4 transition-transform group-hover:translate-x-1">
                                <path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </Link>
            </div>

            <div v-else-if="$page.props.auth.user" class="mt-8">
                <Link :href="dashboard()" class="inline-flex items-center gap-2 rounded-md bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90">
                    Go to Dashboard
                </Link>
            </div>
        </section>

        <!-- How it works -->
        <section class="border-t bg-muted/30 py-20">
            <div class="mx-auto max-w-5xl px-6">
                <h2 class="text-center text-2xl font-bold tracking-tight md:text-3xl">How it works</h2>

                <div class="mt-12 grid gap-12 md:grid-cols-2">
                    <!-- Shop side -->
                    <div class="space-y-6">
                        <h3 class="font-semibold text-primary">For shops</h3>
                        <div class="space-y-5">
                            <div class="flex gap-4">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground">1</span>
                                <div>
                                    <p class="font-medium">Post a service request</p>
                                    <p class="text-sm text-muted-foreground">Describe the shift, set a fixed rate, and choose a date.</p>
                                </div>
                            </div>
                            <div class="flex gap-4">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground">2</span>
                                <div>
                                    <p class="font-medium">A contractor accepts</p>
                                    <p class="text-sm text-muted-foreground">Pre-authorize payment at booking — no surprises.</p>
                                </div>
                            </div>
                            <div class="flex gap-4">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground">3</span>
                                <div>
                                    <p class="font-medium">Mark it complete & rate</p>
                                    <p class="text-sm text-muted-foreground">Payment releases automatically. Leave a review.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Provider side -->
                    <div class="space-y-6">
                        <h3 class="font-semibold text-primary">For contractors</h3>
                        <div class="space-y-5">
                            <div class="flex gap-4">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground">1</span>
                                <div>
                                    <p class="font-medium">Browse available shifts</p>
                                    <p class="text-sm text-muted-foreground">See shifts near you with transparent fixed rates.</p>
                                </div>
                            </div>
                            <div class="flex gap-4">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground">2</span>
                                <div>
                                    <p class="font-medium">Accept what fits your schedule</p>
                                    <p class="text-sm text-muted-foreground">You choose when and where — no obligations.</p>
                                </div>
                            </div>
                            <div class="flex gap-4">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground">3</span>
                                <div>
                                    <p class="font-medium">Get paid via Stripe</p>
                                    <p class="text-sm text-muted-foreground">Direct deposit to your bank — fast, reliable, no cash handling.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t py-8">
            <div class="mx-auto max-w-5xl px-6 flex items-center justify-between text-sm text-muted-foreground">
                <span>© {{ new Date().getFullYear() }} byoe</span>
                <Link :href="login()" class="hover:text-foreground">Log in</Link>
            </div>
        </footer>
    </div>
</template>
