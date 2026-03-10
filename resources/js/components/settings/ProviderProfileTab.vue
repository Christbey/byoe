<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import type { AvailabilityDay, Provider, Rating } from '@/types/marketplace';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardFooter from '@/components/ui/card/CardFooter.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';

interface Completeness {
    percentage: number;
    steps: Record<string, boolean>;
}

interface StarBreakdown {
    count: number;
    percentage: number;
}

interface Props {
    provider: Provider | null;
    canReceivePayouts?: boolean;
    isStripeOnboarded?: boolean;
    completeness?: Completeness;
    schedule?: Record<string, AvailabilityDay>;
    blackoutDates?: string[];
    minNoticeHours?: number;
    recentRatings?: Rating[];
    starBreakdown?: Record<number, StarBreakdown>;
}

const props = withDefaults(defineProps<Props>(), {
    canReceivePayouts: false,
    isStripeOnboarded: false,
    schedule: () => ({}),
    blackoutDates: () => [],
    minNoticeHours: 24,
    recentRatings: () => [],
    starBreakdown: () => ({}),
});

const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

const availabilityForm = useForm({
    schedule: props.schedule,
    blackout_dates: props.blackoutDates,
    min_notice_hours: props.minNoticeHours,
});

const saveAvailability = () => {
    availabilityForm.put('/provider/availability', {
        preserveScroll: true,
    });
};

const availabilityDirty = computed(() => availabilityForm.isDirty);

const newBlackoutDate = ref('');
const addBlackoutDate = () => {
    if (newBlackoutDate.value && !availabilityForm.blackout_dates.includes(newBlackoutDate.value)) {
        availabilityForm.blackout_dates.push(newBlackoutDate.value);
        newBlackoutDate.value = '';
    }
};
const removeBlackoutDate = (date: string) => {
    availabilityForm.blackout_dates = availabilityForm.blackout_dates.filter((d) => d !== date);
};

const formatDate = (date: string) =>
    new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(date + 'T12:00:00'));

const formatRatingDate = (date: string) =>
    new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(date));

const starArray = [5, 4, 3, 2, 1];

const navigateToEdit = () => {
    router.visit('/provider/onboarding');
};
</script>

<template>
    <!-- No Profile Message -->
    <Card v-if="!provider" class="border-dashed">
        <CardContent class="pt-6">
            <div class="flex flex-col items-center justify-center gap-3 py-8 text-center">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                    class="size-12 text-muted-foreground"
                >
                    <path
                        d="M10 8a3 3 0 100-6 3 3 0 000 6zM3.465 14.493a1.23 1.23 0 00.41 1.412A9.957 9.957 0 0010 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 00-13.074.003z"
                    />
                </svg>
                <div class="space-y-1">
                    <h3 class="text-lg font-semibold">Create Your Provider Profile</h3>
                    <p class="text-sm text-muted-foreground max-w-md">
                        Complete your provider profile to start accepting service requests.
                    </p>
                </div>
                <Button variant="default" class="mt-4" @click="navigateToEdit">
                    Create Profile
                </Button>
            </div>
        </CardContent>
    </Card>

    <template v-else>
        <div class="space-y-6">
            <!-- Profile Completeness -->
            <Card v-if="completeness && completeness.percentage < 100" class="border-primary/30 bg-primary/5">
                <CardContent class="pt-4 pb-4">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold">Profile Completeness</h3>
                            <span class="text-sm font-bold text-primary">{{ completeness.percentage }}%</span>
                        </div>
                        <div class="h-2.5 rounded-full bg-muted overflow-hidden">
                            <div
                                class="h-full rounded-full bg-primary transition-all duration-500"
                                :style="{ width: `${completeness.percentage}%` }"
                            />
                        </div>
                        <div class="grid grid-cols-2 gap-x-4 gap-y-1.5 text-xs">
                            <div class="flex items-center gap-1.5" v-for="(done, step) in completeness.steps" :key="step">
                                <svg v-if="done" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3.5 text-primary shrink-0">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3.5 text-muted-foreground shrink-0">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                                </svg>
                                <span :class="done ? '' : 'text-muted-foreground'">
                                    {{ step.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Payout Status -->
            <div class="grid gap-4 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardDescription>Payout Account</CardDescription>
                        <div class="flex items-center gap-2">
                            <Badge v-if="isStripeOnboarded" variant="success">Connected</Badge>
                            <Badge v-else variant="warning">Setup Required</Badge>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <p class="text-sm text-muted-foreground">
                            {{ isStripeOnboarded ? 'Your bank account is connected and ready' : 'Connect your bank account to receive earnings' }}
                        </p>
                        <Link v-if="!isStripeOnboarded" href="/provider/stripe-setup" class="mt-3 inline-block">
                            <Button variant="warning" size="sm">Set Up Payouts</Button>
                        </Link>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardDescription>Payout Eligibility</CardDescription>
                        <div class="flex items-center gap-2">
                            <Badge v-if="canReceivePayouts" variant="default">Eligible</Badge>
                            <Badge v-else variant="secondary">Not Eligible</Badge>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <p class="text-sm text-muted-foreground">
                            {{ canReceivePayouts ? 'You can receive payouts for completed bookings' : 'Complete payout setup to become eligible' }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Profile Stats -->
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <Card>
                    <CardHeader>
                        <CardDescription>Average Rating</CardDescription>
                        <CardTitle class="text-3xl">
                            {{ provider.average_rating?.toFixed(1) || '0.0' }} ⭐
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-muted-foreground">
                            Based on {{ provider.total_ratings || 0 }} ratings
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardDescription>Trust Score</CardDescription>
                        <CardTitle class="text-3xl">
                            {{ provider.trust_score ?? 0 }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center gap-2">
                            <Badge :variant="provider.trust_tier === 'at_risk' ? 'destructive' : provider.trust_tier === 'elite' ? 'success' : provider.trust_tier === 'trusted' ? 'default' : 'outline'">
                                {{ (provider.trust_tier || 'standard').replace('_', ' ') }}
                            </Badge>
                            <span class="text-xs text-muted-foreground">
                                Vetting {{ provider.vetting_status.replace('_', ' ') }}
                            </span>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardDescription>Reliability</CardDescription>
                        <CardTitle class="text-3xl">
                            {{ provider.reliability_score ?? 0 }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-muted-foreground">
                            {{ provider.cancellation_rate?.toFixed(1) || '0.0' }}% cancellation rate · {{ provider.dispute_count || 0 }} disputes
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardDescription>Completed Bookings</CardDescription>
                        <CardTitle class="text-3xl">
                            {{ provider.completed_bookings || 0 }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-muted-foreground">
                            Total jobs completed
                        </p>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Trust & Verification</CardTitle>
                    <CardDescription>How the platform currently evaluates your account</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="rounded-[20px] bg-muted/40 p-4">
                            <p class="text-xs font-medium uppercase tracking-[0.18em] text-muted-foreground">Vetting</p>
                            <div class="mt-2 flex items-center gap-2">
                                <Badge :variant="provider.vetting_status === 'approved' ? 'success' : provider.vetting_status === 'needs_attention' ? 'destructive' : provider.vetting_status === 'suspended' ? 'secondary' : 'warning'">
                                    {{ provider.vetting_status.replace('_', ' ') }}
                                </Badge>
                            </div>
                            <p class="mt-2 text-sm text-muted-foreground">
                                {{ provider.trust_notes || 'No manual review notes yet.' }}
                            </p>
                        </div>
                        <div class="rounded-[20px] bg-muted/40 p-4">
                            <p class="text-xs font-medium uppercase tracking-[0.18em] text-muted-foreground">Background Check</p>
                            <div class="mt-2 flex items-center gap-2">
                                <Badge :variant="provider.background_check_status === 'clear' ? 'success' : provider.background_check_status === 'flagged' ? 'destructive' : 'warning'">
                                    {{ provider.background_check_status }}
                                </Badge>
                            </div>
                            <p class="mt-2 text-sm text-muted-foreground">
                                Identity {{ provider.identity_verified_at ? 'verified' : 'not yet verified' }}
                            </p>
                        </div>
                        <div class="rounded-[20px] bg-muted/40 p-4">
                            <p class="text-xs font-medium uppercase tracking-[0.18em] text-muted-foreground">Operational Record</p>
                            <p class="mt-2 text-sm">
                                {{ provider.completed_without_issue_count || 0 }} clean completions
                            </p>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{ provider.no_show_count || 0 }} no-shows recorded
                            </p>
                        </div>
                    </div>
                    <p v-if="provider.vetting_status !== 'approved'" class="text-sm text-muted-foreground">
                        Your account can still be edited, but payouts and booking confidence stay limited until trust review is approved.
                    </p>
                </CardContent>
            </Card>

            <Card v-if="provider.trust_action_items?.length" class="border-amber-200/70 bg-amber-50/80">
                <CardHeader>
                    <CardTitle>Next Steps</CardTitle>
                    <CardDescription>What to fix next to improve trust and keep your account in good standing.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div
                        v-for="item in provider.trust_action_items"
                        :key="item.title"
                        class="rounded-2xl border border-white/60 bg-white/80 p-4 shadow-sm"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="space-y-1">
                                <p class="text-sm font-semibold text-foreground">{{ item.title }}</p>
                                <p class="text-sm text-muted-foreground">{{ item.detail }}</p>
                            </div>
                            <Badge :variant="item.severity === 'danger' ? 'destructive' : 'outline'">
                                {{ item.severity }}
                            </Badge>
                        </div>
                        <Button as="a" :href="item.action_href" variant="outline" size="sm" class="mt-3">
                            {{ item.action_label }}
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Profile Information -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>Profile Information</CardTitle>
                            <CardDescription>Your professional details</CardDescription>
                        </div>
                        <Badge v-if="provider.is_active" variant="default">
                            Active
                        </Badge>
                        <Badge v-else variant="secondary">
                            Inactive
                        </Badge>
                    </div>
                </CardHeader>

                <CardContent class="space-y-6">
                    <div class="space-y-2">
                        <h3 class="text-sm font-medium text-muted-foreground">Bio</h3>
                        <p class="text-sm">{{ provider.bio || 'No bio provided' }}</p>
                    </div>

                    <div class="space-y-2">
                        <h3 class="text-sm font-medium text-muted-foreground">Years of Experience</h3>
                        <p class="text-sm">{{ provider.years_experience || 0 }} years</p>
                    </div>

                    <div class="space-y-2">
                        <h3 class="text-sm font-medium text-muted-foreground">Skills</h3>
                        <div v-if="provider.skills && provider.skills.length > 0" class="flex flex-wrap gap-2">
                            <Badge
                                v-for="skill in provider.skills"
                                :key="skill"
                                variant="secondary"
                            >
                                {{ skill }}
                            </Badge>
                        </div>
                        <p v-else class="text-sm text-muted-foreground">No skills added</p>
                    </div>
                </CardContent>

                <CardFooter>
                    <Button variant="outline" class="w-full" @click="navigateToEdit">
                        Edit Profile
                    </Button>
                </CardFooter>
            </Card>

            <!-- Availability Schedule -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>Availability Schedule</CardTitle>
                            <CardDescription>Set the days and hours you're available to work</CardDescription>
                        </div>
                        <Badge v-if="availabilityDirty" variant="secondary">Unsaved</Badge>
                    </div>
                </CardHeader>
                <CardContent class="space-y-4">
                    <!-- Weekly Schedule -->
                    <div class="space-y-3">
                        <div
                            v-for="day in days"
                            :key="day"
                            class="flex items-center gap-3"
                        >
                            <!-- Toggle -->
                            <button
                                type="button"
                                @click="availabilityForm.schedule[day].available = !availabilityForm.schedule[day].available"
                                class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                                :class="availabilityForm.schedule[day]?.available ? 'bg-primary' : 'bg-muted'"
                            >
                                <span
                                    class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition-transform"
                                    :class="availabilityForm.schedule[day]?.available ? 'translate-x-4' : 'translate-x-0'"
                                />
                            </button>

                            <!-- Day Label -->
                            <span class="w-24 text-sm font-medium capitalize">{{ day }}</span>

                            <!-- Time Inputs -->
                            <template v-if="availabilityForm.schedule[day]?.available">
                                <input
                                    type="time"
                                    v-model="availabilityForm.schedule[day].start"
                                    class="rounded border border-input bg-background px-2 py-1 text-sm"
                                />
                                <span class="text-sm text-muted-foreground">to</span>
                                <input
                                    type="time"
                                    v-model="availabilityForm.schedule[day].end"
                                    class="rounded border border-input bg-background px-2 py-1 text-sm"
                                />
                            </template>
                            <span v-else class="text-sm text-muted-foreground">Unavailable</span>
                        </div>
                    </div>

                    <!-- Min Notice Hours -->
                    <div class="flex items-center gap-3 pt-2 border-t">
                        <label class="text-sm font-medium min-w-max">Minimum notice</label>
                        <select
                            v-model="availabilityForm.min_notice_hours"
                            class="rounded border border-input bg-background px-2 py-1 text-sm"
                        >
                            <option :value="0">No minimum</option>
                            <option :value="2">2 hours</option>
                            <option :value="4">4 hours</option>
                            <option :value="8">8 hours</option>
                            <option :value="12">12 hours</option>
                            <option :value="24">24 hours</option>
                            <option :value="48">48 hours</option>
                            <option :value="72">72 hours</option>
                        </select>
                    </div>

                    <!-- Blackout Dates -->
                    <div class="space-y-2 pt-2 border-t">
                        <h4 class="text-sm font-medium">Blackout Dates</h4>
                        <p class="text-xs text-muted-foreground">Specific dates you won't be available</p>
                        <div class="flex gap-2">
                            <input
                                type="date"
                                v-model="newBlackoutDate"
                                class="rounded border border-input bg-background px-2 py-1 text-sm flex-1"
                            />
                            <Button type="button" variant="outline" size="sm" @click="addBlackoutDate">Add</Button>
                        </div>
                        <div v-if="availabilityForm.blackout_dates.length > 0" class="flex flex-wrap gap-2">
                            <span
                                v-for="date in availabilityForm.blackout_dates"
                                :key="date"
                                class="inline-flex items-center gap-1 rounded-full bg-muted px-3 py-1 text-xs"
                            >
                                {{ formatDate(date) }}
                                <button type="button" @click="removeBlackoutDate(date)" class="text-muted-foreground hover:text-foreground ml-1">×</button>
                            </span>
                        </div>
                    </div>
                </CardContent>
                <CardFooter>
                    <Button
                        @click="saveAvailability"
                        :disabled="availabilityForm.processing"
                        class="w-full"
                    >
                        {{ availabilityForm.processing ? 'Saving...' : 'Save Availability' }}
                    </Button>
                </CardFooter>
            </Card>

            <!-- Ratings & Reviews -->
            <Card>
                <CardHeader>
                    <CardTitle>Ratings & Reviews</CardTitle>
                    <CardDescription>Feedback from shops you've worked with</CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    <!-- Star Breakdown -->
                    <div v-if="provider.total_ratings && provider.total_ratings > 0" class="space-y-2">
                        <div
                            v-for="star in starArray"
                            :key="star"
                            class="flex items-center gap-3"
                        >
                            <span class="text-xs text-muted-foreground w-4 text-right">{{ star }}</span>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3.5 text-yellow-400 shrink-0">
                                <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd" />
                            </svg>
                            <div class="flex-1 h-2 rounded-full bg-muted overflow-hidden">
                                <div
                                    class="h-full rounded-full bg-yellow-400 transition-all"
                                    :style="{ width: `${starBreakdown[star]?.percentage ?? 0}%` }"
                                />
                            </div>
                            <span class="text-xs text-muted-foreground w-8 text-right">{{ starBreakdown[star]?.count ?? 0 }}</span>
                        </div>
                    </div>

                    <!-- No Ratings Empty State -->
                    <div v-else class="flex flex-col items-center gap-2 py-6 text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-8 text-muted-foreground">
                            <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd" />
                        </svg>
                        <p class="text-sm text-muted-foreground">No reviews yet. Complete bookings to earn ratings.</p>
                    </div>

                    <!-- Recent Reviews -->
                    <div v-if="recentRatings.length > 0" class="space-y-4 pt-2 border-t">
                        <h4 class="text-sm font-medium">Recent Reviews</h4>
                        <div
                            v-for="rating in recentRatings"
                            :key="rating.id"
                            class="space-y-2 pb-4 border-b last:border-0 last:pb-0"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-1">
                                    <svg
                                        v-for="s in 5"
                                        :key="s"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                        class="size-3.5"
                                        :class="s <= rating.rating ? 'text-yellow-400' : 'text-muted'"
                                    >
                                        <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <span class="text-xs text-muted-foreground">{{ formatRatingDate(rating.created_at) }}</span>
                            </div>
                            <p v-if="rating.comment" class="text-sm">{{ rating.comment }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ rating.rater?.name ?? 'Anonymous' }}
                                <span v-if="rating.booking?.service_request"> · {{ rating.booking.service_request.title }}</span>
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Quick Links -->
            <div class="grid gap-3 md:grid-cols-2">
                <Link href="/provider/certifications">
                    <Card class="cursor-pointer hover:border-primary/50 transition-colors">
                        <CardContent class="flex items-center gap-3 pt-4 pb-4">
                            <div class="size-10 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5 text-primary">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-sm">Certifications</p>
                                <p class="text-xs text-muted-foreground">Manage your credentials</p>
                            </div>
                        </CardContent>
                    </Card>
                </Link>

                <Link href="/provider/earnings">
                    <Card class="cursor-pointer hover:border-primary/50 transition-colors">
                        <CardContent class="flex items-center gap-3 pt-4 pb-4">
                            <div class="size-10 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5 text-primary">
                                    <path d="M10.75 10.818v2.614A3.13 3.13 0 0011.888 13c.482-.315.612-.648.612-.875 0-.227-.13-.56-.612-.875a3.13 3.13 0 00-1.138-.432zM8.33 8.62c.053.055.115.11.184.164.208.16.46.284.736.363V6.603a2.45 2.45 0 00-.35.13c-.14.065-.27.143-.386.233-.377.292-.514.627-.514.909 0 .184.058.39.33.615z" />
                                    <path fill-rule="evenodd" d="M9.75 17.25a7.5 7.5 0 100-15 7.5 7.5 0 000 15zM9 11.19v1.81a.75.75 0 001.5 0v-1.81c.411-.143.82-.376 1.144-.64.626-.506 1.106-1.247 1.106-2.1 0-.853-.48-1.594-1.106-2.1-.324-.264-.733-.497-1.144-.64V4.75a.75.75 0 00-1.5 0v1.81c-.411.143-.82.376-1.144.64C6.73 7.706 6.25 8.447 6.25 9.3c0 .853.48 1.594 1.106 2.1.324.264.733.497 1.144.64z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-sm">Earnings & Payouts</p>
                                <p class="text-xs text-muted-foreground">View income and payout history</p>
                            </div>
                        </CardContent>
                    </Card>
                </Link>
            </div>
        </div>
    </template>
</template>
