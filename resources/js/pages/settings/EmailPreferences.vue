<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import Heading from '@/components/Heading.vue';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardFooter from '@/components/ui/card/CardFooter.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import type { BreadcrumbItem } from '@/types';
import { edit } from '@/routes/email-preferences';

interface Props {
    preferences: Record<string, boolean>;
}

const props = defineProps<Props>();

const page = usePage();
const user = computed(() => page.props.auth.user);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Email Preferences', href: edit().url },
];

const form = useForm({
    daily_digest: props.preferences.daily_digest ?? true,
    weekly_summary: props.preferences.weekly_summary ?? true,
    booking_reminders: props.preferences.booking_reminders ?? true,
    new_requests: props.preferences.new_requests ?? true,
    payment_notifications: props.preferences.payment_notifications ?? true,
    rating_notifications: props.preferences.rating_notifications ?? true,
    marketing: props.preferences.marketing ?? true,
});

const submit = () => {
    form.patch(edit().url, {
        preserveScroll: true,
    });
};

interface Preference {
    key: string;
    label: string;
    description: string;
    userTypes?: string[];
}

const emailPreferences: Preference[] = [
    {
        key: 'daily_digest',
        label: 'Daily Shift Digest',
        description: 'Daily email with nearby shifts matching your skills',
        userTypes: ['provider'],
    },
    {
        key: 'weekly_summary',
        label: 'Weekly Summary',
        description: 'Weekly recap of your earnings and bookings',
    },
    {
        key: 'booking_reminders',
        label: 'Booking Reminders',
        description: 'Reminders about upcoming and completed bookings',
    },
    {
        key: 'new_requests',
        label: 'New Request Alerts',
        description: 'Instant notifications when new shifts are posted',
        userTypes: ['provider'],
    },
    {
        key: 'payment_notifications',
        label: 'Payment Notifications',
        description: 'Alerts when payments are received or processed',
    },
    {
        key: 'rating_notifications',
        label: 'Rating Notifications',
        description: 'Notifications when you receive new ratings',
    },
    {
        key: 'marketing',
        label: 'Marketing & Updates',
        description: 'Platform updates, tips, and promotional emails',
    },
];

const userType = user.value?.roles?.[0] || 'user';

const filteredPreferences = emailPreferences.filter(pref => {
    if (!pref.userTypes) return true;
    return pref.userTypes.includes(userType);
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Email Preferences" />

        <h1 class="sr-only">Email Preferences</h1>

        <SettingsLayout>
            <div class="space-y-6">
                <Heading
                    variant="small"
                    title="Email Preferences"
                    description="Choose which emails you'd like to receive"
                />

                <Card>
                <CardContent class="space-y-6 pt-6">
                    <div
                        v-for="preference in filteredPreferences"
                        :key="preference.key"
                        class="flex items-start gap-4 rounded-lg border p-4 transition-colors hover:bg-muted/30"
                    >
                        <button
                            type="button"
                            @click="form[preference.key] = !form[preference.key]"
                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                            :class="form[preference.key] ? 'bg-primary' : 'bg-muted'"
                        >
                            <span
                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition-transform"
                                :class="form[preference.key] ? 'translate-x-5' : 'translate-x-0'"
                            />
                        </button>

                        <div class="flex-1">
                            <Label class="cursor-pointer text-base font-medium">
                                {{ preference.label }}
                            </Label>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{ preference.description }}
                            </p>
                        </div>
                    </div>
                </CardContent>

                <CardFooter class="bg-muted/30">
                    <Button
                        @click="submit"
                        :disabled="form.processing"
                        class="w-full md:w-auto"
                    >
                        {{ form.processing ? 'Saving...' : 'Save Preferences' }}
                    </Button>
                </CardFooter>
            </Card>

            <div class="rounded-lg border border-primary/20 bg-primary/5 p-4">
                <p class="text-sm text-muted-foreground">
                    <strong class="text-foreground">Note:</strong> You'll always receive critical emails about
                    your account security and booking confirmations, regardless of these settings.
                </p>
            </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
