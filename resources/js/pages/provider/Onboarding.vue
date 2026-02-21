<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
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
import { Checkbox } from '@/components/ui/checkbox';
import Icon from '@/components/ui/icon/Icon.vue';

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
    {
        title: 'Provider Onboarding',
        href: '/provider/onboarding',
    },
];

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

const handleSubmit = () => {
    form.put('/provider/profile', {
        preserveScroll: true,
        onSuccess: () => {
            // Will redirect to provider profile/settings after creation
        },
    });
};
</script>

<template>
    <Head title="Provider Onboarding" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 max-w-4xl mx-auto">
            <!-- Onboarding Header -->
            <div class="space-y-3 text-center">
                <div class="flex justify-center">
                    <div class="rounded-full bg-primary/10 p-3">
                        <Icon name="Briefcase" class="size-8 text-primary" />
                    </div>
                </div>
                <h1 class="text-3xl font-bold tracking-tight md:text-4xl">
                    Welcome to the Provider Network
                </h1>
                <p class="text-base text-muted-foreground md:text-lg max-w-2xl mx-auto">
                    Complete your profile to start accepting service requests from coffee shops in your area
                </p>
            </div>

            <!-- Progress Steps -->
            <Card>
                <CardContent class="pt-6">
                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="flex items-start gap-3">
                            <div class="rounded-full bg-primary text-primary-foreground size-8 flex items-center justify-center text-sm font-semibold shrink-0">
                                1
                            </div>
                            <div class="space-y-1">
                                <p class="font-semibold text-sm">Complete Profile</p>
                                <p class="text-xs text-muted-foreground">Add your professional details</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="rounded-full bg-muted text-muted-foreground size-8 flex items-center justify-center text-sm font-semibold shrink-0">
                                2
                            </div>
                            <div class="space-y-1">
                                <p class="font-semibold text-sm">Set Availability</p>
                                <p class="text-xs text-muted-foreground">Configure your schedule</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="rounded-full bg-muted text-muted-foreground size-8 flex items-center justify-center text-sm font-semibold shrink-0">
                                3
                            </div>
                            <div class="space-y-1">
                                <p class="font-semibold text-sm">Setup Payouts</p>
                                <p class="text-xs text-muted-foreground">Connect your Stripe account</p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Profile Form -->
            <Card>
                <form @submit.prevent="handleSubmit">
                    <CardHeader>
                        <CardTitle>Step 1: Professional Profile</CardTitle>
                        <CardDescription>
                            Tell coffee shops about your experience and expertise
                        </CardDescription>
                    </CardHeader>

                    <CardContent class="space-y-6">
                        <!-- Industry Selection -->
                        <div class="space-y-2">
                            <Label for="industry_id">Industry <span class="text-destructive">*</span></Label>
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
                                Choose the industry you work in - this will determine your available skills
                            </p>
                            <p
                                v-if="form.errors.industry_id"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.industry_id }}
                            </p>
                        </div>

                        <!-- Bio -->
                        <div class="space-y-2">
                            <Label for="bio">Professional Bio</Label>
                            <textarea
                                id="bio"
                                v-model="form.bio"
                                rows="4"
                                placeholder="Describe your experience in the coffee industry, your specialties, and what makes you stand out..."
                                class="w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm resize-y"
                            ></textarea>
                            <p class="text-xs text-muted-foreground">
                                This will be shown to shops when they view your profile
                            </p>
                            <p
                                v-if="form.errors.bio"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.bio }}
                            </p>
                        </div>

                        <!-- Years of Experience -->
                        <div class="space-y-2">
                            <Label for="years_experience">
                                Years of Experience in Coffee Industry
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
                            <p
                                v-if="form.errors.years_experience"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.years_experience }}
                            </p>
                        </div>

                        <!-- Skills -->
                        <div class="space-y-3">
                            <div>
                                <Label>Skills & Expertise</Label>
                                <p class="text-xs text-muted-foreground mt-1">
                                    Select skills you can offer or add custom ones
                                </p>
                            </div>

                            <!-- No Industry Selected Message -->
                            <div v-if="!form.industry_id" class="rounded-lg border border-dashed border-border bg-muted/30 p-4 text-center">
                                <p class="text-sm text-muted-foreground">
                                    Please select an industry above to see available skills
                                </p>
                            </div>

                            <!-- Quick Add Common Skills -->
                            <div v-else class="space-y-2">
                                <p class="text-sm font-medium">Common Skills for {{ industries.find(i => i.id === form.industry_id)?.name }}</p>
                                <div class="flex flex-wrap gap-2">
                                    <Badge
                                        v-for="skill in availableSkills.filter(
                                            (s) => !form.skills.includes(s),
                                        )"
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
                                <Button
                                    type="button"
                                    variant="secondary"
                                    @click="addSkill(skillInput)"
                                >
                                    Add
                                </Button>
                            </div>

                            <!-- Selected Skills -->
                            <div
                                v-if="form.skills.length > 0"
                                class="space-y-2"
                            >
                                <p class="text-sm font-medium">Your Skills</p>
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

                            <p
                                v-if="form.errors.skills"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.skills }}
                            </p>
                        </div>

                        <div class="border-t pt-6 space-y-4">
                            <h3 class="text-sm font-semibold">Service Area Preferences</h3>

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
                                    <span class="text-sm text-muted-foreground">miles from home</span>
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    How far are you willing to travel for jobs?
                                </p>
                                <p v-if="form.errors.service_area_max_miles" class="text-sm text-destructive">
                                    {{ form.errors.service_area_max_miles }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label>Preferred Zip Codes (Optional)</Label>
                                <p class="text-xs text-muted-foreground">Add specific zip codes where you prefer to work</p>
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

                        <!-- Active Status Toggle -->
                        <div class="rounded-lg border border-border bg-muted/30 p-4 space-y-3">
                            <div class="flex items-start gap-3">
                                <Checkbox
                                    id="is_active"
                                    :checked="form.is_active"
                                    @update:checked="form.is_active = $event"
                                    class="mt-0.5"
                                />
                                <div class="space-y-1">
                                    <Label
                                        for="is_active"
                                        class="text-sm font-medium cursor-pointer"
                                    >
                                        I'm ready to accept service requests
                                    </Label>
                                    <p class="text-xs text-muted-foreground">
                                        You can change this later in your settings
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>

                    <CardFooter class="flex flex-col gap-3 sm:flex-row bg-muted/30">
                        <Button
                            type="submit"
                            :disabled="form.processing || !form.isDirty"
                            class="w-full sm:flex-1"
                        >
                            <Icon
                                v-if="form.processing"
                                name="Loader2"
                                class="size-4 mr-2 animate-spin"
                            />
                            <span v-if="form.processing">Creating Profile...</span>
                            <span v-else>
                                Create Profile & Continue
                                <Icon name="ArrowRight" size="sm" class="ml-2" />
                            </span>
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            as="a"
                            href="/dashboard"
                            :disabled="form.processing"
                            class="w-full sm:w-auto"
                        >
                            Skip for Now
                        </Button>
                    </CardFooter>
                </form>
            </Card>

            <!-- Info Card -->
            <Card class="border-primary/20 bg-primary/5">
                <CardContent class="pt-6">
                    <div class="flex gap-4">
                        <Icon name="Info" class="size-5 text-primary shrink-0 mt-0.5" />
                        <div class="space-y-2">
                            <p class="text-sm font-medium">What happens next?</p>
                            <ul class="text-sm text-muted-foreground space-y-1 list-disc list-inside">
                                <li>After creating your profile, you'll set your availability schedule</li>
                                <li>Then you'll connect your Stripe account to receive payments</li>
                                <li>Once complete, you can start browsing and accepting service requests</li>
                            </ul>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
