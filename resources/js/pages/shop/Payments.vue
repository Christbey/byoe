<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { loadStripe, type Stripe, type StripeElements } from '@stripe/stripe-js';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { PaginatedResponse } from '@/types/marketplace';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardFooter from '@/components/ui/card/CardFooter.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';

interface Payment {
    id: number;
    booking_id: number;
    amount: number;
    status: 'authorized' | 'succeeded' | 'failed' | 'refunded' | 'pending';
    payment_method: string;
    stripe_payment_intent_id?: string;
    created_at: string;
    updated_at: string;
    booking?: {
        id: number;
        service_request?: { title: string };
    };
}

interface PendingAuthorization {
    id: number;
    title: string;
    price: number;
    stripe_payment_intent_id: string;
    created_at: string;
}

interface SavedCard {
    brand: string;
    last4: string;
    exp_month: number;
    exp_year: number;
}

interface Props {
    tab?: string;
    payments: PaginatedResponse<Payment>;
    pendingAuthorizations?: PendingAuthorization[];
    filter?: string;
    clientSecret?: string | null;
    stripePublishableKey?: string | null;
    savedCard?: SavedCard | null;
}

const props = withDefaults(defineProps<Props>(), {
    tab: 'history',
    filter: 'all',
    pendingAuthorizations: () => [],
    clientSecret: null,
    stripePublishableKey: null,
    savedCard: null,
});

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Payments', href: '/shop/payments' }];

const activeFilter = ref(props.filter);

// ─── Tab switching ─────────────────────────────────────────────────────────────

const switchTab = (tab: string) => {
    router.get('/shop/payments', { tab }, { preserveState: false });
};

// ─── History tab ──────────────────────────────────────────────────────────────

const filters = [
    { key: 'all', label: 'All' },
    { key: 'succeeded', label: 'Succeeded' },
    { key: 'failed', label: 'Failed' },
    { key: 'refunded', label: 'Refunded' },
];

const handleFilterChange = (filterKey: string) => {
    activeFilter.value = filterKey;
    router.get('/shop/payments', { tab: 'history', filter: filterKey }, { preserveState: true, preserveScroll: true });
};

const formatCurrency = (amount: number) =>
    new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);

const formatDate = (date: string) =>
    new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }).format(new Date(date));

const getStatusColor = (status: string) =>
    ({ authorized: 'secondary', succeeded: 'default', pending: 'secondary', failed: 'destructive', refunded: 'outline' }[status] ?? 'outline');

const statusLabel = (status: string) =>
    ({ authorized: 'On Hold', succeeded: 'Paid', pending: 'Pending', failed: 'Failed', refunded: 'Refunded', processing: 'Processing' }[status] ?? status);

const handleDownloadReceipt = (paymentId: number) => {
    window.open(`/shop/payments/${paymentId}/receipt`, '_blank');
};

// ─── Payment method tab ───────────────────────────────────────────────────────

const stripe = ref<Stripe | null>(null);
const elements = ref<StripeElements | null>(null);
const mountTarget = ref<HTMLElement | null>(null);
const isLoading = ref(false);
const isSubmitting = ref(false);
const errorMessage = ref<string | null>(null);
const showUpdateForm = ref(!props.savedCard);

const brandLabel = (brand: string): string => {
    const labels: Record<string, string> = { visa: 'Visa', mastercard: 'Mastercard', amex: 'American Express', discover: 'Discover', jcb: 'JCB', diners: 'Diners Club', unionpay: 'UnionPay' };
    return labels[brand] ?? brand.charAt(0).toUpperCase() + brand.slice(1);
};

const formatExpiry = (month: number, year: number): string =>
    `${String(month).padStart(2, '0')}/${String(year).slice(-2)}`;

const initStripeElements = async (): Promise<void> => {
    if (!props.clientSecret || !props.stripePublishableKey) { return; }

    isLoading.value = true;
    errorMessage.value = null;

    try {
        stripe.value = await loadStripe(props.stripePublishableKey);

        if (!stripe.value) {
            errorMessage.value = 'Failed to load payment processor. Please refresh and try again.';
            isLoading.value = false;
            return;
        }

        elements.value = stripe.value.elements({
            clientSecret: props.clientSecret,
            appearance: {
                theme: 'stripe',
                variables: { colorPrimary: '#0F172A', colorBackground: '#ffffff', colorText: '#1F2937', colorDanger: '#EF4444', fontFamily: 'system-ui, sans-serif', borderRadius: '8px' },
            },
        });

        const paymentElement = elements.value.create('payment');

        if (mountTarget.value) {
            paymentElement.mount(mountTarget.value);
            paymentElement.on('ready', () => { isLoading.value = false; });
        }
    } catch {
        errorMessage.value = 'Failed to initialize payment form. Please refresh and try again.';
        isLoading.value = false;
    }
};

onMounted(async () => {
    if (props.tab === 'method' && !props.savedCard) {
        await initStripeElements();
    }
});

const handleShowUpdateForm = async (): Promise<void> => {
    showUpdateForm.value = true;
    await initStripeElements();
};

const handleSubmit = async (): Promise<void> => {
    if (!stripe.value || !elements.value) { return; }

    isSubmitting.value = true;
    errorMessage.value = null;

    const { error, setupIntent } = await stripe.value.confirmSetup({
        elements: elements.value,
        confirmParams: { return_url: window.location.href },
        redirect: 'if_required',
    });

    if (error) {
        errorMessage.value = error.message ?? 'Failed to save card. Please try again.';
        isSubmitting.value = false;
        return;
    }

    if (setupIntent?.payment_method) {
        const paymentMethodId = typeof setupIntent.payment_method === 'string'
            ? setupIntent.payment_method
            : setupIntent.payment_method.id;

        router.post('/shop/payment/save', { payment_method_id: paymentMethodId }, {
            onSuccess: () => { showUpdateForm.value = false; },
            onError: () => { errorMessage.value = 'Card authorized but failed to save. Please try again.'; },
            onFinish: () => { isSubmitting.value = false; },
        });
    } else {
        errorMessage.value = 'Setup completed but no payment method returned. Please try again.';
        isSubmitting.value = false;
    }
};
</script>

<template>
    <Head title="Payments" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
            <!-- Header -->
            <div class="space-y-1">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">Payments</h1>
                <p class="text-sm text-muted-foreground md:text-base">
                    {{ tab === 'method' ? 'Manage your saved payment card for service requests' : 'View and manage your payment transactions' }}
                </p>
            </div>

            <!-- Tabs -->
            <div class="flex gap-1 border-b">
                <button
                    class="px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px"
                    :class="tab === 'history' ? 'border-primary text-foreground' : 'border-transparent text-muted-foreground hover:text-foreground'"
                    @click="switchTab('history')"
                >
                    History
                </button>
                <button
                    class="px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px"
                    :class="tab === 'method' ? 'border-primary text-foreground' : 'border-transparent text-muted-foreground hover:text-foreground'"
                    @click="switchTab('method')"
                >
                    Payment Method
                    <span v-if="savedCard" class="ml-1.5 rounded-full bg-green-100 text-green-700 px-1.5 py-0.5 text-xs">Active</span>
                </button>
            </div>

            <!-- ── HISTORY TAB ── -->
            <template v-if="tab === 'history'">
                <!-- Pending Authorizations -->
                <Card v-if="pendingAuthorizations && pendingAuthorizations.length > 0" class="border-amber-300 bg-amber-50 dark:bg-amber-950/20">
                    <CardContent class="pt-6 space-y-3">
                        <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">Card Holds — Awaiting Provider</p>
                        <p class="text-xs text-amber-700 dark:text-amber-400">
                            These amounts are authorized on your card and will only be charged when a provider accepts the request. The hold is released automatically if the request expires.
                        </p>
                        <div class="divide-y divide-amber-200 dark:divide-amber-800">
                            <div v-for="auth in pendingAuthorizations" :key="auth.id" class="flex items-center justify-between py-3">
                                <div>
                                    <a :href="`/shop/service-requests/${auth.id}`" class="text-sm font-medium hover:underline">{{ auth.title }}</a>
                                    <p class="text-xs text-muted-foreground">{{ formatDate(auth.created_at) }}</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-semibold">{{ formatCurrency(auth.price) }}</span>
                                    <Badge variant="secondary">On Hold</Badge>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Filter Tabs -->
                <div class="flex gap-2 overflow-x-auto pb-2 -mx-4 px-4 md:mx-0 md:px-0">
                    <Badge
                        v-for="f in filters"
                        :key="f.key"
                        :variant="activeFilter === f.key ? 'default' : 'outline'"
                        as="button"
                        class="cursor-pointer whitespace-nowrap px-4 py-2 text-sm font-medium transition-colors touch-manipulation"
                        @click="handleFilterChange(f.key)"
                    >
                        {{ f.label }}
                    </Badge>
                </div>

                <!-- Desktop Table -->
                <Card class="hidden md:block">
                    <CardContent class="p-0">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="border-b bg-muted/50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Description</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Booking</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr v-for="payment in payments.data" :key="payment.id" class="hover:bg-muted/50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ formatDate(payment.created_at) }}</td>
                                        <td class="px-6 py-4 text-sm">{{ payment.booking?.service_request?.title || 'Service Payment' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold">{{ formatCurrency(payment.amount) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <Badge :variant="getStatusColor(payment.status)">{{ statusLabel(payment.status) }}</Badge>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">
                                            <a :href="`/shop/bookings/${payment.booking_id}`" class="hover:underline">#{{ payment.booking_id }}</a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                            <Button v-if="payment.status === 'succeeded'" variant="ghost" size="sm" @click="handleDownloadReceipt(payment.id)">Receipt</Button>
                                        </td>
                                    </tr>
                                    <tr v-if="payments.data.length === 0">
                                        <td colspan="6" class="px-6 py-12 text-center text-sm text-muted-foreground">No payments found.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                <!-- Mobile Cards -->
                <div v-if="payments.data.length > 0" class="space-y-4 md:hidden">
                    <Card v-for="payment in payments.data" :key="payment.id">
                        <CardContent class="pt-6 space-y-3">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-sm truncate">{{ payment.booking?.service_request?.title || 'Service Payment' }}</p>
                                    <p class="text-xs text-muted-foreground">{{ formatDate(payment.created_at) }}</p>
                                </div>
                                <Badge :variant="getStatusColor(payment.status)">{{ statusLabel(payment.status) }}</Badge>
                            </div>
                            <div class="flex items-center justify-between pt-2 border-t">
                                <div>
                                    <p class="text-xl font-bold">{{ formatCurrency(payment.amount) }}</p>
                                    <p class="text-xs text-muted-foreground">Booking <a :href="`/shop/bookings/${payment.booking_id}`" class="hover:underline">#{{ payment.booking_id }}</a></p>
                                </div>
                                <Button v-if="payment.status === 'succeeded'" variant="outline" size="sm" @click="handleDownloadReceipt(payment.id)">Receipt</Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Empty state (mobile) -->
                <Card v-if="payments.data.length === 0" class="md:hidden">
                    <CardContent class="flex flex-col items-center justify-center py-12 px-4 text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-16 text-muted-foreground mb-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                        </svg>
                        <h3 class="text-lg font-semibold mb-2">No payments found</h3>
                        <p class="text-sm text-muted-foreground max-w-md">Payments will appear here once you complete bookings.</p>
                    </CardContent>
                </Card>

                <!-- Pagination -->
                <div v-if="payments.data.length > 0" class="text-center text-sm text-muted-foreground pb-4">
                    Page {{ payments.current_page }} of {{ payments.last_page }}
                </div>
            </template>

            <!-- ── PAYMENT METHOD TAB ── -->
            <template v-if="tab === 'method'">
                <div class="max-w-2xl">
                    <!-- No shop profile -->
                    <Card v-if="!clientSecret && !savedCard">
                        <CardContent class="py-8 text-center">
                            <p class="text-sm text-muted-foreground">No shop profile found. Please set up your shop profile first.</p>
                            <Button as="a" href="/shop/profile/edit" class="mt-4">Set Up Shop Profile</Button>
                        </CardContent>
                    </Card>

                    <template v-else>
                        <!-- Saved Card Summary -->
                        <Card v-if="savedCard && !showUpdateForm">
                            <CardHeader>
                                <div class="flex items-center gap-3">
                                    <CardTitle>Saved Card</CardTitle>
                                    <Badge variant="default">Active</Badge>
                                </div>
                                <CardDescription>This card is charged automatically when you post service requests</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="rounded-lg border bg-muted/50 p-4 space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Card</span>
                                        <span class="font-medium">{{ brandLabel(savedCard.brand) }} •••• {{ savedCard.last4 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Expires</span>
                                        <span>{{ formatExpiry(savedCard.exp_month, savedCard.exp_year) }}</span>
                                    </div>
                                </div>
                            </CardContent>
                            <CardFooter>
                                <Button variant="outline" @click="handleShowUpdateForm">Update Card</Button>
                            </CardFooter>
                        </Card>

                        <!-- Add / Update Card Form -->
                        <Card v-if="showUpdateForm">
                            <CardHeader>
                                <CardTitle>{{ savedCard ? 'Update Card' : 'Add Payment Card' }}</CardTitle>
                                <CardDescription>
                                    {{ savedCard ? 'Enter new card details to replace your saved card.' : 'Add a card to use for service request authorizations.' }}
                                </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div v-if="isLoading" class="space-y-3 animate-pulse">
                                    <div class="h-10 rounded-md bg-muted"></div>
                                    <div class="h-10 rounded-md bg-muted"></div>
                                    <div class="h-10 rounded-md bg-muted w-2/3"></div>
                                </div>
                                <div ref="mountTarget" :class="{ hidden: isLoading }"></div>
                                <p v-if="errorMessage" class="text-sm text-destructive">{{ errorMessage }}</p>
                                <Button v-if="!isLoading" class="w-full" :disabled="isSubmitting" @click="handleSubmit">
                                    {{ isSubmitting ? 'Saving…' : savedCard ? 'Update Card' : 'Save Card' }}
                                </Button>
                                <p class="text-center text-xs text-muted-foreground">Secured by Stripe</p>
                            </CardContent>
                            <CardFooter v-if="savedCard">
                                <Button variant="ghost" @click="showUpdateForm = false">Cancel</Button>
                            </CardFooter>
                        </Card>
                    </template>
                </div>
            </template>
        </div>
    </AppLayout>
</template>
