<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardFooter from '@/components/ui/card/CardFooter.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import Badge from '@/components/ui/badge/Badge.vue';
import Icon from '@/components/ui/icon/Icon.vue';
import type { BreadcrumbItem } from '@/types';

interface Industry {
    id: number;
    name: string;
}

interface Props {
    industries: Industry[];
    industrySkills: Record<number, string[]>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Provider Onboarding', href: '/provider/onboarding' },
];

const currentStep = ref(1);
const totalSteps = 2; // Profile + Stripe (availability can be done later in settings)

const form = useForm({
    industry_id: null as number | null,
    bio: '',
    skills: [] as string[],
    years_experience: 0,
    is_active: true,
    service_area_max_miles: 25,
    preferred_zip_codes: [] as string[],
});

const availableSkills = computed(() => {
    if (!form.industry_id) return [];
    return props.industrySkills[form.industry_id] || [];
});

const skillInput = ref('');

const addSkill = (skill: string) => {
    if (skill && !form.skills.includes(skill)) {
        form.skills.push(skill);
        skillInput.value = '';
    }
};

const removeSkill = (skill: string) => {
    const index = form.skills.indexOf(skill);
    if (index > -1) {
        form.skills.splice(index, 1);
    }
};

const zipInput = ref('');

const addZip = (zip: string) => {
    const clean = zip.trim();
    if (clean && /^\d{5}(-\d{4})?$/.test(clean) && !form.preferred_zip_codes.includes(clean)) {
        form.preferred_zip_codes.push(clean);
        zipInput.value = '';
    }
};

const removeZip = (zip: string) => {
    const idx = form.preferred_zip_codes.indexOf(zip);
    if (idx > -1) form.preferred_zip_codes.splice(idx, 1);
};

const canProceedToStep2 = computed(() => {
    return form.industry_id && form.skills.length > 0;
});

const nextStep = () => {
    if (currentStep.value < totalSteps) {
        currentStep.value++;
    }
};

const previousStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

const submitProfile = () => {
    form.put('/provider/profile', {
        preserveScroll: true,
        onSuccess: () => {
            // Profile created, move to Stripe setup
            currentStep.value = 2;
        },
    });
};

const skipStripeForNow = () => {
    router.visit('/provider/dashboard');
};

const setupStripe = () => {
    router.visit('/provider/stripe-setup');
};
</script>

<template>
    <Head title="Provider Onboarding" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 max-w-4xl mx-auto">
            <!-- Header -->
            <div class="space-y-3 text-center">
                <div class="flex justify-center">
                    <div class="rounded-full bg-primary/10 p-3">
                        <Icon name="Briefcase" class="size-8 text-primary" />
                    </div>
                </div>
                <h1 class="text-3xl font-bold tracking-tight md:text-4xl">
                    Welcome to ShiftFinder
                </h1>
                <p class="text-base text-muted-foreground md:text-lg max-w-2xl mx-auto">
                    Let's get you set up to start accepting service requests
                </p>
            </div>

            <!-- Progress Steps -->
            <Card>
                <CardContent class="pt-6">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="flex items-start gap-3">
                            <div
                                class="rounded-full size-8 flex items-center justify-center text-sm font-semibold shrink-0"
                                :class="currentStep >= 1 ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'"
                            >
                                <Icon v-if="currentStep > 1" name="Check" class="size-4" />
                                <span v-else>1</span>
                            </div>
                            <div class="space-y-1">
                                <p class="font-semibold text-sm">Profile Setup</p>
                                <p class="text-xs text-muted-foreground">Industry, skills & experience</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div
                                class="rounded-full size-8 flex items-center justify-center text-sm font-semibold shrink-0"
                                :class="currentStep >= 2 ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'"
                            >
                                2
                            </div>
                            <div class="space-y-1">
                                <p class="font-semibold text-sm">Payment Setup</p>
                                <p class="text-xs text-muted-foreground">Connect Stripe for payouts</p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Step 1: Profile Setup -->
            <Card v-show="currentStep === 1">
                <CardHeader>
                    <CardTitle>Step 1: Your Professional Profile</CardTitle>
                    <CardDescription>
                        Tell us about your industry and skills
                    </CardDescription>
                </CardHeader>

                <CardContent class="space-y-6">
                    <!-- Industry Selection -->
                    <div class="space-y-2">
                        <Label for="industry_id">
                            What industry do you work in? <span class="text-destructive">*</span>
                        </Label>
                        <select
                            id="industry_id"
                            v-model="form.industry_id"
                            required
                            class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                        >
                            <option :value="null" disabled>Select your industry...</option>
                            <option v-for="industry in industries" :key="industry.id" :value="industry.id">
                                {{ industry.name }}
                            </option>
                        </select>
                        <p class="text-xs text-muted-foreground">
                            This determines which skills are available to you
                        </p>
                        <p v-if="form.errors.industry_id" class="text-sm text-destructive">
                            {{ form.errors.industry_id }}
                        </p>
                    </div>

                    <!-- Years of Experience -->
                    <div class="space-y-2">
                        <Label for="years_experience">
                            Years of Experience <span class="text-destructive">*</span>
                        </Label>
                        <Input
                            id="years_experience"
                            v-model="form.years_experience"
                            type="number"
                            min="0"
                            max="50"
                            required
                            placeholder="0"
                        />
                        <p v-if="form.errors.years_experience" class="text-sm text-destructive">
                            {{ form.errors.years_experience }}
                        </p>
                    </div>

                    <!-- Bio -->
                    <div class="space-y-2">
                        <Label for="bio">Professional Bio (Optional)</Label>
                        <textarea
                            id="bio"
                            v-model="form.bio"
                            rows="4"
                            placeholder="Tell shops about your experience..."
                            class="w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm resize-y"
                        ></textarea>
                        <p v-if="form.errors.bio" class="text-sm text-destructive">
                            {{ form.errors.bio }}
                        </p>
                    </div>

                    <!-- Skills -->
                    <div class="space-y-3">
                        <div>
                            <Label>Your Skills <span class="text-destructive">*</span></Label>
                            <p class="text-xs text-muted-foreground mt-1">
                                Select at least one skill
                            </p>
                        </div>

                        <!-- No Industry Selected -->
                        <div v-if="!form.industry_id" class="rounded-lg border border-dashed border-border bg-muted/30 p-4 text-center">
                            <p class="text-sm text-muted-foreground">
                                Please select an industry above to see available skills
                            </p>
                        </div>

                        <!-- Industry-Specific Skills -->
                        <div v-else class="space-y-2">
                            <p class="text-sm font-medium">{{ industries.find(i => i.id === form.industry_id)?.name }} Skills</p>
                            <div class="flex flex-wrap gap-2">
                                <Badge
                                    v-for="skill in availableSkills.filter(s => !form.skills.includes(s))"
                                    :key="skill"
                                    variant="outline"
                                    as="button"
                                    type="button"
                                    @click="addSkill(skill)"
                                    class="cursor-pointer hover:bg-accent transition-colors"
                                >
                                    <Icon name="Plus" size="xs" class="mr-1" />
                                    {{ skill }}
                                </Badge>
                            </div>
                        </div>

                        <!-- Custom Skill Input -->
                        <div class="flex gap-2">
                            <Input
                                v-model="skillInput"
                                type="text"
                                placeholder="Add a custom skill"
                                @keydown.enter.prevent="addSkill(skillInput)"
                                class="flex-1"
                            />
                            <Button type="button" variant="secondary" @click="addSkill(skillInput)">
                                Add
                            </Button>
                        </div>

                        <!-- Selected Skills -->
                        <div v-if="form.skills.length > 0" class="space-y-2">
                            <p class="text-sm font-medium">Your Selected Skills</p>
                            <div class="flex flex-wrap gap-2">
                                <Badge
                                    v-for="skill in form.skills"
                                    :key="skill"
                                    variant="default"
                                    as="button"
                                    type="button"
                                    @click="removeSkill(skill)"
                                    class="cursor-pointer hover:opacity-80 transition-opacity"
                                >
                                    {{ skill }}
                                    <Icon name="X" size="xs" class="ml-1" />
                                </Badge>
                            </div>
                        </div>

                        <p v-if="form.errors.skills" class="text-sm text-destructive">
                            {{ form.errors.skills }}
                        </p>
                    </div>

                    <!-- Service Area -->
                    <div class="border-t pt-6 space-y-4">
                        <h3 class="text-sm font-semibold">Service Area (Optional)</h3>

                        <div class="space-y-2">
                            <Label for="service_area_max_miles">Maximum Travel Distance</Label>
                            <div class="flex items-center gap-3 max-w-xs">
                                <Input
                                    id="service_area_max_miles"
                                    v-model.number="form.service_area_max_miles"
                                    type="number"
                                    min="1"
                                    max="500"
                                    class="w-24"
                                />
                                <span class="text-sm text-muted-foreground">miles</span>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label>Preferred Zip Codes</Label>
                            <div class="flex gap-2">
                                <Input
                                    v-model="zipInput"
                                    type="text"
                                    placeholder="e.g. 90210"
                                    maxlength="10"
                                    class="w-32"
                                    @keydown.enter.prevent="addZip(zipInput)"
                                />
                                <Button type="button" variant="secondary" @click="addZip(zipInput)">Add</Button>
                            </div>
                            <div v-if="form.preferred_zip_codes.length > 0" class="flex flex-wrap gap-2">
                                <Badge
                                    v-for="zip in form.preferred_zip_codes"
                                    :key="zip"
                                    variant="secondary"
                                    as="button"
                                    type="button"
                                    @click="removeZip(zip)"
                                    class="cursor-pointer hover:opacity-80"
                                >
                                    {{ zip }}
                                    <Icon name="X" size="xs" class="ml-1" />
                                </Badge>
                            </div>
                        </div>
                    </div>
                </CardContent>

                <CardFooter class="bg-muted/30">
                    <Button
                        @click="submitProfile"
                        :disabled="form.processing || !canProceedToStep2"
                        class="w-full"
                    >
                        <Icon v-if="form.processing" name="Loader2" class="size-4 mr-2 animate-spin" />
                        {{ form.processing ? 'Saving...' : 'Save & Continue' }}
                        <Icon v-if="!form.processing" name="ArrowRight" size="sm" class="ml-2" />
                    </Button>
                </CardFooter>
            </Card>

            <!-- Step 2: Stripe Setup -->
            <Card v-show="currentStep === 2">
                <CardHeader>
                    <CardTitle>Step 2: Payment Setup</CardTitle>
                    <CardDescription>
                        Connect your bank account to receive payments
                    </CardDescription>
                </CardHeader>

                <CardContent class="space-y-6">
                    <div class="flex flex-col items-center gap-4 py-8 text-center">
                        <div class="rounded-full bg-green-100 dark:bg-green-900/20 p-4">
                            <Icon name="DollarSign" class="size-12 text-green-600 dark:text-green-400" />
                        </div>
                        <div class="space-y-2">
                            <h3 class="text-lg font-semibold">Get Paid for Your Work</h3>
                            <p class="text-sm text-muted-foreground max-w-md">
                                Connect your Stripe account to receive payments for completed bookings. This is required before you can accept jobs.
                            </p>
                        </div>
                    </div>

                    <div class="rounded-lg border border-primary/20 bg-primary/5 p-4">
                        <div class="flex gap-3">
                            <Icon name="Info" class="size-5 text-primary shrink-0 mt-0.5" />
                            <div class="space-y-1 text-sm">
                                <p class="font-medium">What you'll need:</p>
                                <ul class="list-disc list-inside space-y-1 text-muted-foreground">
                                    <li>Bank account details</li>
                                    <li>Social Security Number or EIN</li>
                                    <li>Photo ID</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </CardContent>

                <CardFooter class="flex flex-col gap-3 bg-muted/30">
                    <Button @click="setupStripe" class="w-full">
                        <Icon name="CreditCard" class="size-4 mr-2" />
                        Connect Stripe Account
                    </Button>
                    <Button @click="skipStripeForNow" variant="outline" class="w-full">
                        Skip for Now (Setup Later in Settings)
                    </Button>
                </CardFooter>
            </Card>
        </div>
    </AppLayout>
</template>
