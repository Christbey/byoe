<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { AvailabilityDay } from '@/types/marketplace';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardFooter from '@/components/ui/card/CardFooter.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Props {
    needsProfile: boolean;
    schedule: Record<string, AvailabilityDay>;
    blackoutDates: string[];
    minNoticeHours: number;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Provider Profile', href: '/provider/profile' },
    { title: 'Availability', href: '/provider/availability' },
];

const days = [
    { key: 'monday', label: 'Monday' },
    { key: 'tuesday', label: 'Tuesday' },
    { key: 'wednesday', label: 'Wednesday' },
    { key: 'thursday', label: 'Thursday' },
    { key: 'friday', label: 'Friday' },
    { key: 'saturday', label: 'Saturday' },
    { key: 'sunday', label: 'Sunday' },
];

const form = useForm({
    schedule: { ...props.schedule },
    blackout_dates: [...props.blackoutDates],
    min_notice_hours: props.minNoticeHours,
});

const newBlackoutDate = ref('');

const addBlackoutDate = () => {
    if (newBlackoutDate.value && !form.blackout_dates.includes(newBlackoutDate.value)) {
        form.blackout_dates.push(newBlackoutDate.value);
        newBlackoutDate.value = '';
    }
};

const removeBlackoutDate = (date: string) => {
    const idx = form.blackout_dates.indexOf(date);
    if (idx > -1) {
        form.blackout_dates.splice(idx, 1);
    }
};

const formatBlackoutDate = (date: string) => {
    return new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(date + 'T00:00:00'));
};

const handleSubmit = () => {
    form.put('/provider/availability', { preserveScroll: true });
};
</script>

<template>
    <Head title="Availability Schedule" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6 max-w-3xl">
            <div class="space-y-2">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">Availability Schedule</h1>
                <p class="text-sm text-muted-foreground">Set the days and hours you're available to work</p>
            </div>

            <div v-if="needsProfile" class="rounded-lg border border-dashed p-8 text-center">
                <p class="text-muted-foreground">Create your provider profile first before setting availability.</p>
                <a href="/provider/profile/edit" class="mt-4 inline-block">
                    <Button>Create Profile</Button>
                </a>
            </div>

            <form v-else @submit.prevent="handleSubmit" class="space-y-4">
                <!-- Weekly Schedule -->
                <Card>
                    <CardHeader>
                        <CardTitle>Weekly Schedule</CardTitle>
                        <CardDescription>Toggle days you're available and set your working hours</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div
                            v-for="day in days"
                            :key="day.key"
                            class="flex items-center gap-4 rounded-lg border p-3 transition-colors"
                            :class="form.schedule[day.key]?.available ? 'border-primary/30 bg-primary/5' : 'border-border bg-muted/20'"
                        >
                            <!-- Toggle -->
                            <button
                                type="button"
                                class="flex h-6 w-11 shrink-0 items-center rounded-full border-2 transition-colors focus:outline-none"
                                :class="form.schedule[day.key]?.available ? 'border-primary bg-primary' : 'border-muted-foreground/30 bg-muted'"
                                @click="form.schedule[day.key].available = !form.schedule[day.key].available"
                            >
                                <span
                                    class="mx-0.5 block size-4 rounded-full bg-white shadow transition-transform"
                                    :class="form.schedule[day.key]?.available ? 'translate-x-5' : 'translate-x-0'"
                                />
                            </button>

                            <!-- Day label -->
                            <span class="w-28 font-medium text-sm">{{ day.label }}</span>

                            <!-- Time pickers -->
                            <template v-if="form.schedule[day.key]?.available">
                                <div class="flex flex-1 items-center gap-2 flex-wrap">
                                    <div class="flex items-center gap-1">
                                        <Label :for="`${day.key}-start`" class="sr-only">Start time</Label>
                                        <Input
                                            :id="`${day.key}-start`"
                                            v-model="form.schedule[day.key].start"
                                            type="time"
                                            class="w-32"
                                        />
                                    </div>
                                    <span class="text-muted-foreground text-sm">to</span>
                                    <div class="flex items-center gap-1">
                                        <Label :for="`${day.key}-end`" class="sr-only">End time</Label>
                                        <Input
                                            :id="`${day.key}-end`"
                                            v-model="form.schedule[day.key].end"
                                            type="time"
                                            class="w-32"
                                        />
                                    </div>
                                </div>
                            </template>
                            <span v-else class="flex-1 text-sm text-muted-foreground">Not available</span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Minimum Notice -->
                <Card>
                    <CardHeader>
                        <CardTitle>Minimum Notice</CardTitle>
                        <CardDescription>How many hours advance notice do you need before a shift?</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center gap-3 max-w-xs">
                            <Input
                                v-model.number="form.min_notice_hours"
                                type="number"
                                min="0"
                                max="168"
                                class="w-24"
                            />
                            <span class="text-sm text-muted-foreground">hours</span>
                        </div>
                        <p v-if="form.errors.min_notice_hours" class="mt-1 text-sm text-destructive">
                            {{ form.errors.min_notice_hours }}
                        </p>
                    </CardContent>
                </Card>

                <!-- Blackout Dates -->
                <Card>
                    <CardHeader>
                        <CardTitle>Blackout Dates</CardTitle>
                        <CardDescription>Specific dates you're unavailable (vacations, appointments, etc.)</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div class="flex gap-2">
                            <Input
                                v-model="newBlackoutDate"
                                type="date"
                                class="flex-1 max-w-xs"
                            />
                            <Button type="button" variant="secondary" @click="addBlackoutDate">
                                Add Date
                            </Button>
                        </div>

                        <div v-if="form.blackout_dates.length > 0" class="flex flex-wrap gap-2">
                            <span
                                v-for="date in form.blackout_dates.slice().sort()"
                                :key="date"
                                class="inline-flex items-center gap-1 rounded-full bg-muted px-3 py-1 text-sm"
                            >
                                {{ formatBlackoutDate(date) }}
                                <button
                                    type="button"
                                    class="ml-1 rounded-full hover:text-destructive transition-colors"
                                    @click="removeBlackoutDate(date)"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3.5">
                                        <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                                    </svg>
                                </button>
                            </span>
                        </div>
                        <p v-else class="text-sm text-muted-foreground">No blackout dates set</p>
                    </CardContent>
                    <CardFooter>
                        <Button type="submit" :disabled="form.processing" class="w-full sm:w-auto">
                            {{ form.processing ? 'Saving...' : 'Save Availability' }}
                        </Button>
                    </CardFooter>
                </Card>
            </form>
        </div>
    </AppLayout>
</template>
