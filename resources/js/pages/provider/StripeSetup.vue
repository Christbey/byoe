<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { loadConnectAndInitialize } from '@stripe/connect-js';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardFooter from '@/components/ui/card/CardFooter.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';

interface StripeAccount {
    id: string;
    charges_enabled: boolean;
    payouts_enabled: boolean;
    details_submitted: boolean;
    created_at: string;
}

interface Props {
    needsProfile?: boolean;
    stripe_account?: StripeAccount | null;
    accountSessionSecret?: string | null;
    stripePublishableKey?: string | null;
    isOnboardingComplete?: boolean;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Stripe Setup', href: '/provider/stripe-setup' },
];

const onboardingContainer = ref<HTMLElement | null>(null);
const managementContainer = ref<HTMLElement | null>(null);
const isLoading = ref(true);
const loadError = ref<string | null>(null);
const isDashboardLinkLoading = ref(false);
const dashboardLinkError = ref<string | null>(null);

const formatDate = (date: string) =>
    new Intl.DateTimeFormat('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    }).format(new Date(date));

// Read the XSRF-TOKEN cookie (refreshed by Laravel on every response) and send it
// as X-XSRF-TOKEN. The meta tag csrf-token is only written at page-render time and
// becomes stale after the Stripe SDK calls fetchClientSecret and gets a new response.
const xsrfToken = (): string => {
    const cookie = document.cookie.split('; ').find((row) => row.startsWith('XSRF-TOKEN='));
    return cookie ? decodeURIComponent(cookie.split('=')[1]) : '';
};

// Fetch a fresh Account Session client_secret from the server.
// Called on mount and again automatically if the session expires (~1 hour).
const fetchClientSecret = async (): Promise<string> => {
    const response = await fetch('/provider/stripe-setup/session', {
        method: 'POST',
        headers: {
            'X-XSRF-TOKEN': xsrfToken(),
            Accept: 'application/json',
        },
    });

    if (!response.ok) {
        throw new Error('Failed to create Stripe session');
    }

    const data = await response.json();
    return data.client_secret;
};

// Open the Stripe Express Dashboard in a new tab.
// The blank window must be opened synchronously (before any await) so the browser
// doesn't treat it as an async popup and silently block it.
const openExpressDashboard = async (): Promise<void> => {
    isDashboardLinkLoading.value = true;
    dashboardLinkError.value = null;

    // Open a blank tab immediately (synchronously) to preserve the user-gesture context —
    // browsers block window.open() called inside an async callback.
    // noopener is intentionally omitted here: with noopener, window.open() returns null
    // in the opener, so we'd have no reference to navigate to the Stripe URL.
    const newWindow = window.open('about:blank', '_blank');

    if (!newWindow) {
        dashboardLinkError.value = 'Popup blocked. Please allow popups for this site and try again.';
        isDashboardLinkLoading.value = false;
        return;
    }

    try {
        const response = await fetch('/provider/stripe-setup/dashboard-link', {
            method: 'POST',
            headers: {
                'X-XSRF-TOKEN': xsrfToken(),
                Accept: 'application/json',
            },
        });

        const data = await response.json();

        if (!response.ok) {
            newWindow.close();
            dashboardLinkError.value = data.error ?? 'Unable to open Stripe Dashboard. Please try again.';
            return;
        }

        newWindow.location.href = data.url;
    } catch {
        newWindow.close();
        dashboardLinkError.value = 'Unable to open Stripe Dashboard. Please try again.';
    } finally {
        isDashboardLinkLoading.value = false;
    }
};

onMounted(async () => {
    if (!props.stripe_account || !props.accountSessionSecret) {
        isLoading.value = false;
        return;
    }

    try {
        const stripe = loadConnectAndInitialize({
            publishableKey: props.stripePublishableKey!,
            fetchClientSecret,
            appearance: {
                variables: {
                    fontFamily: 'inherit',
                    colorPrimary: '#18181b',
                    borderRadius: '8px',
                },
            },
        });

        if (props.isOnboardingComplete) {
            // Mount the account-management component so the provider can update
            // their bank account, personal details, etc. without leaving the app.
            if (managementContainer.value) {
                const accountManagement = stripe.create('account-management');
                managementContainer.value.appendChild(accountManagement);
            }
        } else {
            // Mount the onboarding component for providers still completing setup.
            if (onboardingContainer.value) {
                const accountOnboarding = stripe.create('account-onboarding');

                // When the provider finishes or exits, reload to get updated account status
                accountOnboarding.setOnExit(async () => {
                    // Sync account status from Stripe before reloading so
                    // isOnboardingComplete is accurate without waiting for a webhook.
                    try {
                        await fetch('/provider/stripe-setup/sync', {
                            method: 'POST',
                            headers: { 'X-XSRF-TOKEN': xsrfToken(), Accept: 'application/json' },
                        });
                    } catch {
                        // Best-effort — reload regardless
                    }
                    router.visit('/provider/stripe-setup', { replace: true });
                });

                onboardingContainer.value.appendChild(accountOnboarding);
            }
        }
    } catch (err) {
        loadError.value = 'Unable to load the setup form. Please try refreshing the page.';
    } finally {
        isLoading.value = false;
    }
});
</script>

<template>
    <Head title="Stripe Setup" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6 max-w-3xl">
            <!-- Header -->
            <div class="space-y-2">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">Payout Setup</h1>
                <p class="text-sm text-muted-foreground md:text-base">
                    Connect your bank account to receive earnings from completed bookings
                </p>
            </div>

            <!-- Needs profile first -->
            <Card v-if="needsProfile">
                <CardHeader>
                    <CardTitle>Complete Your Profile First</CardTitle>
                    <CardDescription>You need a provider profile before setting up payouts.</CardDescription>
                </CardHeader>
                <CardFooter>
                    <Button as="a" href="/provider/profile/edit">Set Up Profile</Button>
                </CardFooter>
            </Card>

            <!-- Fully onboarded — account management + Express Dashboard link -->
            <template v-else-if="isOnboardingComplete && stripe_account">
                <Card>
                    <CardHeader>
                        <div class="flex items-center gap-3">
                            <CardTitle>Payout Account</CardTitle>
                            <Badge variant="default">Active</Badge>
                        </div>
                        <CardDescription>Your account is verified and ready to receive payouts</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="rounded-lg border p-4 space-y-1">
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5 text-green-600">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                    </svg>
                                    <p class="font-medium text-sm">Charges Enabled</p>
                                </div>
                                <p class="text-xs text-muted-foreground">Payments can be captured to your account</p>
                            </div>
                            <div class="rounded-lg border p-4 space-y-1">
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5 text-green-600">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                    </svg>
                                    <p class="font-medium text-sm">Payouts Enabled</p>
                                </div>
                                <p class="text-xs text-muted-foreground">Funds transfer to your bank automatically</p>
                            </div>
                        </div>
                        <div class="rounded-lg border border-border bg-muted/50 p-4 space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Account ID</span>
                                <code class="text-xs">{{ stripe_account.id }}</code>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Connected</span>
                                <span>{{ formatDate(stripe_account.created_at) }}</span>
                            </div>
                        </div>

                        <!-- Loading skeleton for account-management component -->
                        <div v-if="isLoading" class="space-y-3 animate-pulse">
                            <div class="h-10 rounded-md bg-muted"></div>
                            <div class="h-10 rounded-md bg-muted"></div>
                            <div class="h-10 rounded-md bg-muted w-2/3"></div>
                        </div>

                        <!-- Error state -->
                        <div v-else-if="loadError" class="rounded-lg border border-destructive/50 bg-destructive/10 p-4">
                            <p class="text-sm text-destructive">{{ loadError }}</p>
                            <Button variant="outline" size="sm" class="mt-3" @click="router.visit('/provider/stripe-setup')">
                                Retry
                            </Button>
                        </div>

                        <!-- Stripe account-management component mounts here -->
                        <div ref="managementContainer"></div>
                    </CardContent>
                    <CardFooter class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <!-- Express Dashboard link — view balance, payouts, 1099s -->
                        <div class="flex flex-col gap-1 w-full sm:w-auto">
                            <Button
                                variant="outline"
                                :disabled="isDashboardLinkLoading"
                                @click="openExpressDashboard"
                            >
                                <svg v-if="isDashboardLinkLoading" class="mr-2 size-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="mr-2 size-4">
                                    <path fill-rule="evenodd" d="M4.25 5.5a.75.75 0 00-.75.75v8.5c0 .414.336.75.75.75h8.5a.75.75 0 00.75-.75v-4a.75.75 0 011.5 0v4A2.25 2.25 0 0112.75 17h-8.5A2.25 2.25 0 012 14.75v-8.5A2.25 2.25 0 014.25 4h5a.75.75 0 010 1.5h-5z" clip-rule="evenodd" />
                                    <path fill-rule="evenodd" d="M6.194 12.753a.75.75 0 001.06.053L16.5 4.44v2.81a.75.75 0 001.5 0v-4.5a.75.75 0 00-.75-.75h-4.5a.75.75 0 000 1.5h2.553l-9.056 8.194a.75.75 0 00-.053 1.06z" clip-rule="evenodd" />
                                </svg>
                                {{ isDashboardLinkLoading ? 'Opening…' : 'Open Stripe Dashboard' }}
                            </Button>
                            <p v-if="dashboardLinkError" class="text-xs text-destructive">{{ dashboardLinkError }}</p>
                        </div>
                        <Button as="a" href="/provider/dashboard" variant="ghost">Back to Dashboard</Button>
                    </CardFooter>
                </Card>
            </template>

            <!-- Onboarding in progress — embedded component -->
            <template v-else-if="stripe_account">
                <Card>
                    <CardHeader>
                        <CardTitle>Set Up Your Payout Account</CardTitle>
                        <CardDescription>
                            Enter your information below — you'll stay right here in the app.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <!-- Loading skeleton -->
                        <div v-if="isLoading" class="space-y-3 animate-pulse">
                            <div class="h-10 rounded-md bg-muted"></div>
                            <div class="h-10 rounded-md bg-muted"></div>
                            <div class="h-10 rounded-md bg-muted w-2/3"></div>
                        </div>

                        <!-- Error state -->
                        <div v-else-if="loadError" class="rounded-lg border border-destructive/50 bg-destructive/10 p-4">
                            <p class="text-sm text-destructive">{{ loadError }}</p>
                            <Button variant="outline" size="sm" class="mt-3" @click="router.visit('/provider/stripe-setup')">
                                Retry
                            </Button>
                        </div>

                        <!-- Stripe embedded component mounts here -->
                        <div ref="onboardingContainer"></div>
                    </CardContent>
                </Card>
            </template>

            <!-- No account yet (creation failed) -->
            <Card v-else>
                <CardHeader>
                    <CardTitle>Unable to Create Account</CardTitle>
                    <CardDescription>There was a problem setting up your payout account.</CardDescription>
                </CardHeader>
                <CardContent>
                    <p class="text-sm text-muted-foreground">Please refresh the page to try again. If the problem persists, contact support.</p>
                </CardContent>
                <CardFooter>
                    <Button @click="router.visit('/provider/stripe-setup')">Try Again</Button>
                </CardFooter>
            </Card>
        </div>
    </AppLayout>
</template>
