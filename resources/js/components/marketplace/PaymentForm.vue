<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { loadStripe, type Stripe, type StripeElements } from '@stripe/stripe-js';

interface Props {
    clientSecret: string;
    publishableKey: string;
    amount: number;
    returnUrl?: string;
    buttonLabel?: string;
}

const props = defineProps<Props>();
const emit = defineEmits<{ success: [] }>();

const stripe = ref<Stripe | null>(null);
const elements = ref<StripeElements | null>(null);
const isLoading = ref(true);
const isSubmitting = ref(false);
const errorMessage = ref<string | null>(null);
const mountTarget = ref<HTMLElement | null>(null);

const formatCurrency = (amount: number) =>
    new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);

onMounted(async () => {
    try {
        stripe.value = await loadStripe(props.publishableKey);

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
        errorMessage.value = 'Failed to initialize payment form.';
        isLoading.value = false;
    }
});

const handleSubmit = async () => {
    if (!stripe.value || !elements.value) return;

    isSubmitting.value = true;
    errorMessage.value = null;

    const { error } = await stripe.value.confirmPayment({
        elements: elements.value,
        confirmParams: {
            return_url: props.returnUrl ?? window.location.href,
        },
        redirect: 'if_required',
    });

    if (error) {
        errorMessage.value = error.message ?? 'Payment failed. Please try again.';
        isSubmitting.value = false;
    } else {
        // Non-redirect payment methods (cards) resolve here on success
        emit('success');
    }
};
</script>

<template>
    <div class="space-y-4">
        <div v-if="isLoading" class="flex items-center justify-center py-8">
            <div class="h-6 w-6 animate-spin rounded-full border-2 border-primary border-t-transparent" />
            <span class="ml-2 text-sm text-muted-foreground">Loading payment form...</span>
        </div>

        <div ref="mountTarget" :class="{ hidden: isLoading }" />

        <p v-if="errorMessage" class="text-sm text-destructive">
            {{ errorMessage }}
        </p>

        <Button
            v-if="!isLoading"
            class="w-full"
            :disabled="isSubmitting"
            @click="handleSubmit"
        >
            {{ isSubmitting ? 'Processing...' : (buttonLabel ?? `Pay ${formatCurrency(amount)}`) }}
        </Button>

        <p class="text-center text-xs text-muted-foreground">
            Secured by Stripe
        </p>
    </div>
</template>
