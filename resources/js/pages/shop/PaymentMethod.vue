<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { loadStripe, type Stripe, type StripeElements } from '@stripe/stripe-js';
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

interface SavedCard {
    brand: string;
    last4: string;
    exp_month: number;
    exp_year: number;
}

interface Props {
    clientSecret: string | null;
    stripePublishableKey: string | null;
    savedCard: SavedCard | null;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Payment Method', href: '/shop/payment' }];

const stripe = ref<Stripe | null>(null);
const elements = ref<StripeElements | null>(null);
const mountTarget = ref<HTMLElement | null>(null);
const isLoading = ref(false);
const isSubmitting = ref(false);
const errorMessage = ref<string | null>(null);
const showUpdateForm = ref(!props.savedCard);

const brandLabel = (brand: string): string => {
    const labels: Record<string, string> = {
        visa: 'Visa',
        mastercard: 'Mastercard',
        amex: 'American Express',
        discover: 'Discover',
        jcb: 'JCB',
        diners: 'Diners Club',
        unionpay: 'UnionPay',
    };
    return labels[brand] ?? brand.charAt(0).toUpperCase() + brand.slice(1);
};

const formatExpiry = (month: number, year: number): string =>
    `${String(month).padStart(2, '0')}/${String(year).slice(-2)}`;

const initStripeElements = async (): Promise<void> => {
    if (!props.clientSecret || !props.stripePublishableKey) {
        return;
    }

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
                variables: {
                    colorPrimary: '#0F172A',
                    colorBackground: '#ffffff',
                    colorText: '#1F2937',
                    colorDanger: '#EF4444',
                    fontFamily: 'system-ui, sans-serif',
                    borderRadius: '8px',
                },
            },
        });

        const paymentElement = elements.value.create('payment');

        if (mountTarget.value) {
            paymentElement.mount(mountTarget.value);
            paymentElement.on('ready', () => {
                isLoading.value = false;
            });
        }
    } catch {
        errorMessage.value = 'Failed to initialize payment form. Please refresh and try again.';
        isLoading.value = false;
    }
};

onMounted(async () => {
    if (!props.savedCard) {
        await initStripeElements();
    }
});

const handleShowUpdateForm = async (): Promise<void> => {
    showUpdateForm.value = true;
    await initStripeElements();
};

const handleSubmit = async (): Promise<void> => {
    if (!stripe.value || !elements.value) {
        return;
    }

    isSubmitting.value = true;
    errorMessage.value = null;

    const { error, setupIntent } = await stripe.value.confirmSetup({
        elements: elements.value,
        confirmParams: {
            return_url: window.location.href,
        },
        redirect: 'if_required',
    });

    if (error) {
        errorMessage.value = error.message ?? 'Failed to save card. Please try again.';
        isSubmitting.value = false;
        return;
    }

    if (setupIntent?.payment_method) {
        const paymentMethodId =
            typeof setupIntent.payment_method === 'string'
                ? setupIntent.payment_method
                : setupIntent.payment_method.id;

        router.post('/shop/payment/save', { payment_method_id: paymentMethodId }, {
            onSuccess: () => {
                showUpdateForm.value = false;
            },
            onError: () => {
                errorMessage.value = 'Card authorized but failed to save. Please try again.';
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        });
    } else {
        errorMessage.value = 'Setup completed but no payment method returned. Please try again.';
        isSubmitting.value = false;
    }
};
</script>

<template>
    <Head title="Payment Method" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6 max-w-2xl">
            <div class="space-y-2">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">Payment Method</h1>
                <p class="text-sm text-muted-foreground md:text-base">
                    Save your card to authorize service requests without re-entering your details each time.
                </p>
            </div>

            <!-- No shop profile -->
            <Card v-if="!clientSecret && !savedCard">
                <CardContent class="py-8 text-center">
                    <p class="text-sm text-muted-foreground">
                        No shop profile found. Please set up your shop profile first.
                    </p>
                    <Button as="a" href="/settings/profile?tab=shop" class="mt-4">Set Up Shop Profile</Button>
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
                        <CardDescription>
                            This card is charged automatically when you post service requests
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="rounded-lg border bg-muted/50 p-4 space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Card</span>
                                <span class="font-medium">
                                    {{ brandLabel(savedCard.brand) }} •••• {{ savedCard.last4 }}
                                </span>
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
                            {{
                                savedCard
                                    ? 'Enter new card details to replace your saved card.'
                                    : 'Add a card to use for service request authorizations.'
                            }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Loading skeleton -->
                        <div v-if="isLoading" class="space-y-3 animate-pulse">
                            <div class="h-10 rounded-md bg-muted"></div>
                            <div class="h-10 rounded-md bg-muted"></div>
                            <div class="h-10 rounded-md bg-muted w-2/3"></div>
                        </div>

                        <!-- Stripe Payment Element mounts here -->
                        <div ref="mountTarget" :class="{ hidden: isLoading }"></div>

                        <p v-if="errorMessage" class="text-sm text-destructive">{{ errorMessage }}</p>

                        <Button
                            v-if="!isLoading"
                            class="w-full"
                            :disabled="isSubmitting"
                            @click="handleSubmit"
                        >
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
    </AppLayout>
</template>
