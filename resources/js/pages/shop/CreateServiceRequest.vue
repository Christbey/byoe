<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { ShopLocation } from '@/types/marketplace';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardFooter from '@/components/ui/card/CardFooter.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import MobileTapButton from '@/components/marketplace/MobileTapButton.vue';
import PageHelp from '@/components/marketplace/PageHelp.vue';

interface ServiceTemplate {
    title: string;
    description: string;
    skills: string[];
}

interface Props {
    locations: ShopLocation[];
    skills: string[];
    templates: ServiceTemplate[];
    minimumWages: Record<string, number>;
    federalMinimumWage: number;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Service Requests', href: '/shop/service-requests' },
    { title: 'Create Request', href: '/shop/service-requests/create' },
];

const selectedTemplate = ref<ServiceTemplate | null>(null);

const form = useForm({
    shop_location_id: props.locations[0]?.id || null,
    title: '',
    description: '',
    skills_required: [] as string[],
    service_date: '',
    start_time: '',
    end_time: '',
    price: '',
});

// — Template picker —
const selectTemplate = (template: ServiceTemplate) => {
    selectedTemplate.value = template;
    form.title = template.title;
    form.description = template.description;
    form.skills_required = [...template.skills];
};

const clearTemplate = () => {
    selectedTemplate.value = null;
    form.title = '';
    form.description = '';
    form.skills_required = [];
};

// — Skills —
const skillInput = ref('');
const availableSkills = computed(() => props.skills);

const addSkill = (skill: string) => {
    const trimmed = skill.trim();
    if (trimmed && !form.skills_required.includes(trimmed)) {
        form.skills_required.push(trimmed);
        skillInput.value = '';
    }
};

const removeSkill = (skill: string) => {
    const index = form.skills_required.indexOf(skill);
    if (index > -1) form.skills_required.splice(index, 1);
};

// — Duration options —
const durationOptions = [
    { label: '1 hour', value: 1 },
    { label: '2 hours', value: 2 },
    { label: '3 hours', value: 3 },
    { label: '4 hours', value: 4 },
    { label: '5 hours', value: 5 },
    { label: '6 hours', value: 6 },
    { label: '7 hours', value: 7 },
    { label: '8 hours', value: 8 },
    { label: '10 hours', value: 10 },
    { label: '12 hours', value: 12 },
];

const selectedDuration = ref<number | null>(null);

// Recompute end_time whenever start or duration changes
watch([() => form.start_time, () => form.service_date, selectedDuration], () => {
    if (!form.start_time || !selectedDuration.value) {
        form.end_time = '';
        return;
    }
    const [hours, minutes] = form.start_time.split(':').map(Number);
    const totalMinutes = hours * 60 + minutes + selectedDuration.value * 60;
    const endHours = Math.floor(totalMinutes / 60) % 24;
    const endMins = totalMinutes % 60;
    form.end_time = `${String(endHours).padStart(2, '0')}:${String(endMins).padStart(2, '0')}`;
});

// — Location & minimum wage —
const selectedLocation = computed(() =>
    props.locations.find((loc) => loc.id === form.shop_location_id),
);

const locationState = computed(() => selectedLocation.value?.state?.toUpperCase() ?? '');

const minimumHourlyWage = computed(() => {
    if (!locationState.value) return props.federalMinimumWage;
    return props.minimumWages[locationState.value] ?? props.federalMinimumWage;
});

// — Minimum price based on duration —
const minimumPrice = computed(() => {
    if (!selectedDuration.value) return 0;
    return Math.ceil(minimumHourlyWage.value * selectedDuration.value * 100) / 100;
});

const priceBelowMinimum = computed(() => {
    if (!form.price || minimumPrice.value <= 0) return false;
    return parseFloat(form.price) < minimumPrice.value;
});

// Auto-set price to minimum when duration or location changes
watch(minimumPrice, (newMin) => {
    if (newMin > 0 && (!form.price || parseFloat(form.price) < newMin)) {
        form.price = newMin.toFixed(2);
    }
});

// — Dates —
const minDate = computed(() => new Date().toISOString().split('T')[0]);

// Formatted end time for display
const displayEndTime = computed(() => {
    if (!form.end_time) return null;
    const [h, m] = form.end_time.split(':').map(Number);
    const date = new Date();
    date.setHours(h, m);
    return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
});

const canSubmit = computed(() =>
    selectedTemplate.value !== null &&
    form.shop_location_id &&
    form.service_date &&
    form.start_time &&
    selectedDuration.value &&
    form.price &&
    !priceBelowMinimum.value,
);

const handleSubmit = () => {
    form.post('/shop/service-requests', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            selectedTemplate.value = null;
            selectedDuration.value = null;
        },
    });
};
</script>

<template>
    <Head title="Create Service Request" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 max-w-3xl">
            <!-- Header -->
            <div class="space-y-1">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">Create Service Request</h1>
                <p class="text-sm text-muted-foreground">Post a shift to find qualified workers for your location.</p>
                <PageHelp storage-key="shop-create-request" :steps="['Choose a request type (template) that describes the role you need filled.', 'Select which of your locations the shift is at, then add any specific skills required.', 'Pick the date, start time, and shift duration — the end time is calculated for you.', 'Set the total pay for the shift. The minimum wage floor for your state is enforced automatically.', 'Once posted, the request goes live immediately and providers can accept it.']" />
            </div>

            <!-- Step 1: Template Picker -->
            <div class="space-y-3">
                <div class="flex items-center gap-2">
                    <span class="flex size-6 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground">1</span>
                    <h2 class="font-semibold">Choose a Request Type</h2>
                </div>

                <!-- Selected template preview -->
                <div v-if="selectedTemplate" class="rounded-lg border border-primary/40 bg-primary/5 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="space-y-1">
                            <p class="font-medium">{{ selectedTemplate.title }}</p>
                            <p class="text-sm text-muted-foreground">{{ selectedTemplate.description }}</p>
                        </div>
                        <Button type="button" variant="ghost" size="sm" @click="clearTemplate" class="shrink-0">
                            Change
                        </Button>
                    </div>
                </div>

                <!-- Template grid -->
                <div v-else class="grid gap-2 sm:grid-cols-2">
                    <button
                        v-for="(template, i) in templates"
                        :key="i"
                        type="button"
                        @click="selectTemplate(template)"
                        class="flex flex-col items-start gap-1 rounded-lg border border-input bg-background p-3 text-left text-sm transition-colors hover:border-primary/50 hover:bg-accent hover:text-accent-foreground"
                    >
                        <span class="font-medium">{{ template.title }}</span>
                        <span class="line-clamp-2 text-xs text-muted-foreground">{{ template.description }}</span>
                    </button>
                </div>
            </div>

            <!-- Steps 2–5 only appear after a template is selected -->
            <template v-if="selectedTemplate">

                <!-- Step 2: Location -->
                <div class="space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="flex size-6 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground">2</span>
                        <h2 class="font-semibold">Select Location</h2>
                    </div>
                    <select
                        v-model="form.shop_location_id"
                        required
                        class="w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] md:text-sm"
                    >
                        <option v-for="location in locations" :key="location.id" :value="location.id">
                            {{ location.address_line_1 }}, {{ location.city }}, {{ location.state }} {{ location.zip_code }}
                        </option>
                    </select>
                    <p v-if="form.errors.shop_location_id" class="text-sm text-destructive">{{ form.errors.shop_location_id }}</p>
                </div>

                <!-- Step 3: Skills -->
                <div class="space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="flex size-6 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground">3</span>
                        <h2 class="font-semibold">Skills Required</h2>
                    </div>

                    <!-- Custom skill input -->
                    <div class="flex gap-2">
                        <Input
                            v-model="skillInput"
                            type="text"
                            placeholder="Add a custom skill"
                            @keydown.enter.prevent="addSkill(skillInput)"
                            class="flex-1"
                        />
                        <Button type="button" variant="secondary" @click="addSkill(skillInput)">Add</Button>
                    </div>

                    <!-- Suggestions -->
                    <div class="flex flex-wrap gap-2">
                        <Badge
                            v-for="skill in availableSkills.filter((s) => !form.skills_required.includes(s))"
                            :key="skill"
                            variant="outline"
                            as="button"
                            type="button"
                            @click="addSkill(skill)"
                            class="cursor-pointer hover:bg-accent transition-colors"
                        >
                            + {{ skill }}
                        </Badge>
                    </div>

                    <!-- Selected skills -->
                    <div v-if="form.skills_required.length > 0" class="flex flex-wrap gap-2">
                        <Badge
                            v-for="skill in form.skills_required"
                            :key="skill"
                            variant="default"
                            as="button"
                            type="button"
                            @click="removeSkill(skill)"
                            class="cursor-pointer hover:opacity-80 transition-opacity"
                        >
                            {{ skill }}
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3 ml-1">
                                <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                            </svg>
                        </Badge>
                    </div>
                    <p v-if="form.errors.skills_required" class="text-sm text-destructive">{{ form.errors.skills_required }}</p>
                </div>

                <!-- Step 4: Date & Time -->
                <div class="space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="flex size-6 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground">4</span>
                        <h2 class="font-semibold">Date & Time</h2>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="service_date">Service Date</Label>
                            <Input id="service_date" v-model="form.service_date" type="date" required :min="minDate" />
                            <p v-if="form.errors.service_date" class="text-sm text-destructive">{{ form.errors.service_date }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="start_time">Start Time</Label>
                            <Input id="start_time" v-model="form.start_time" type="time" required />
                            <p v-if="form.errors.start_time" class="text-sm text-destructive">{{ form.errors.start_time }}</p>
                        </div>
                    </div>

                    <!-- Duration selector -->
                    <div class="space-y-2">
                        <Label>Duration</Label>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="opt in durationOptions"
                                :key="opt.value"
                                type="button"
                                @click="selectedDuration = opt.value"
                                class="rounded-md border px-3 py-1.5 text-sm font-medium transition-colors"
                                :class="selectedDuration === opt.value
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : 'border-input bg-background hover:bg-accent hover:text-accent-foreground'"
                            >
                                {{ opt.label }}
                            </button>
                        </div>
                        <p v-if="form.end_time && displayEndTime" class="text-sm text-muted-foreground">
                            Shift ends at <span class="font-medium text-foreground">{{ displayEndTime }}</span>
                        </p>
                        <p v-if="form.errors.end_time" class="text-sm text-destructive">{{ form.errors.end_time }}</p>
                    </div>
                </div>

                <!-- Step 5: Price -->
                <div class="space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="flex size-6 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground">5</span>
                        <h2 class="font-semibold">Service Price</h2>
                    </div>

                    <!-- Shift cost summary -->
                    <div v-if="selectedDuration && locationState" class="rounded-lg bg-muted px-4 py-3 text-sm space-y-0.5">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">{{ locationState }} minimum wage</span>
                            <span class="font-medium">${{ minimumHourlyWage.toFixed(2) }}/hr</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Shift length</span>
                            <span class="font-medium">{{ selectedDuration }}h</span>
                        </div>
                        <div class="flex justify-between border-t border-border/50 pt-1.5 mt-1.5">
                            <span class="text-muted-foreground">Minimum total</span>
                            <span class="font-semibold text-foreground">${{ minimumPrice.toFixed(2) }}</span>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="price">
                            Amount to Pay Provider
                            <span v-if="minimumPrice > 0" class="ml-1 text-xs text-muted-foreground font-normal">
                                (min ${{ minimumPrice.toFixed(2) }})
                            </span>
                        </Label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">$</span>
                            <Input
                                id="price"
                                v-model="form.price"
                                type="number"
                                required
                                :min="minimumPrice > 0 ? minimumPrice : 0"
                                step="0.01"
                                placeholder="0.00"
                                class="pl-7"
                                :class="{ 'border-destructive focus-visible:ring-destructive/50': priceBelowMinimum }"
                            />
                        </div>
                        <div v-if="priceBelowMinimum" class="flex items-center gap-1.5 text-sm text-destructive">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4 shrink-0">
                                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                            </svg>
                            Price cannot be below the {{ locationState }} minimum wage floor of ${{ minimumPrice.toFixed(2) }}.
                        </div>
                        <p v-else-if="form.errors.price" class="text-sm text-destructive">{{ form.errors.price }}</p>
                        <p class="text-xs text-muted-foreground">
                            A 15% platform fee is deducted — provider receives the remainder.
                        </p>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex flex-col gap-3 sm:flex-row pb-6">
                    <MobileTapButton
                        type="button"
                        variant="primary"
                        :loading="form.processing"
                        :disabled="form.processing || !canSubmit"
                        class="sm:flex-1"
                        @click="handleSubmit"
                    >
                        <template #loading>Creating Request...</template>
                        Post Service Request
                    </MobileTapButton>
                    <Button
                        type="button"
                        variant="outline"
                        as="a"
                        href="/shop/service-requests"
                        :disabled="form.processing"
                        class="w-full sm:w-auto sm:flex-1"
                    >
                        Cancel
                    </Button>
                </div>

            </template>
        </div>
    </AppLayout>
</template>
