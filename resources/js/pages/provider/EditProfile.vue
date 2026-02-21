<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { Provider } from '@/types/marketplace';
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

interface Props {
    provider: Provider | null;
    availableSkills: string[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Provider Settings',
        href: '/settings/provider',
    },
    {
        title: 'Edit Profile',
        href: '/provider/profile/edit',
    },
];


const form = useForm({
    bio: props.provider?.bio || '',
    skills: props.provider?.skills || [],
    years_experience: props.provider?.years_experience || 0,
    is_active: props.provider?.is_active ?? true,
    service_area_max_miles: props.provider?.service_area_max_miles ?? 25,
    preferred_zip_codes: props.provider?.preferred_zip_codes ?? [],
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
            // Redirect to show page after successful update
        },
    });
};
</script>

<template>
    <Head title="Edit Provider Profile" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6 max-w-3xl">
            <!-- Header -->
            <div class="space-y-2">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                    {{ provider ? 'Edit Provider Profile' : 'Create Provider Profile' }}
                </h1>
                <p class="text-sm text-muted-foreground md:text-base">
                    {{ provider ? 'Update your professional details' : 'Complete your profile to get started' }}
                </p>
            </div>

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
                                Complete the form below to set up your provider profile and start accepting service requests from coffee shops.
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Profile Stats -->
            <div v-else class="grid gap-4 md:grid-cols-2">
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

            <!-- Profile Form -->
            <Card>
                <form @submit.prevent="handleSubmit">
                    <CardHeader>
                        <CardTitle>
                            {{ provider ? 'Profile Information' : 'Create Your Profile' }}
                        </CardTitle>
                        <CardDescription>
                            {{ provider ? 'Update your professional details' : 'Enter your professional details to get started' }}
                        </CardDescription>
                    </CardHeader>

                    <CardContent class="space-y-6">
                        <!-- Bio -->
                        <div class="space-y-2">
                            <Label for="bio">Bio</Label>
                            <textarea
                                id="bio"
                                v-model="form.bio"
                                rows="4"
                                placeholder="Tell shops about your experience and expertise..."
                                class="w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm resize-y"
                            ></textarea>
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
                                Years of Experience
                            </Label>
                            <Input
                                id="years_experience"
                                v-model="form.years_experience"
                                type="number"
                                min="0"
                                max="50"
                                required
                            />
                            <p
                                v-if="form.errors.years_experience"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.years_experience }}
                            </p>
                        </div>

                        <!-- Skills -->
                        <div class="space-y-2">
                            <Label>Skills</Label>
                            <div class="flex gap-2">
                                <Input
                                    v-model="skillInput"
                                    type="text"
                                    placeholder="Add a skill"
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

                            <!-- Available Skills -->
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
                                    + {{ skill }}
                                </Badge>
                            </div>

                            <!-- Selected Skills -->
                            <div
                                v-if="form.skills.length > 0"
                                class="flex flex-wrap gap-2 pt-2"
                            >
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
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                        class="size-3 ml-1"
                                    >
                                        <path
                                            d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"
                                        />
                                    </svg>
                                </Badge>
                            </div>

                            <p
                                v-if="form.errors.skills"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.skills }}
                            </p>
                        </div>

                        <!-- Active Status Toggle -->
                        <div class="space-y-2">
                            <div class="flex items-center gap-3">
                                <Checkbox
                                    id="is_active"
                                    :checked="form.is_active"
                                    @update:checked="form.is_active = $event"
                                />
                                <Label
                                    for="is_active"
                                    class="text-sm font-medium cursor-pointer"
                                >
                                    Active status - Available for bookings
                                </Label>
                            </div>
                            <p class="text-xs text-muted-foreground ml-6">
                                When active, you'll be visible to shops and can
                                accept service requests
                            </p>
                        </div>

                        <!-- Service Area -->
                        <div class="space-y-4 rounded-lg border border-border p-4">
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
                                <p v-if="form.errors.service_area_max_miles" class="text-sm text-destructive">
                                    {{ form.errors.service_area_max_miles }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label>Preferred Zip Codes</Label>
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
                                        {{ zip }} ×
                                    </Badge>
                                </div>
                            </div>
                        </div>

                        <!-- Profile Photo Note -->
                        <div class="rounded-lg border border-border bg-muted/50 p-4">
                            <div class="flex gap-3">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                    class="size-5 text-muted-foreground shrink-0 mt-0.5"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                <div class="space-y-1">
                                    <p class="text-sm font-medium">
                                        Profile Photo
                                    </p>
                                    <p class="text-sm text-muted-foreground">
                                        Profile photo upload will be available in a
                                        future update.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>

                    <CardFooter class="flex flex-col gap-3 sm:flex-row">
                        <Button
                            type="submit"
                            :disabled="form.processing || (!provider && !form.isDirty)"
                            class="w-full sm:flex-1"
                        >
                            <svg
                                v-if="form.processing"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                                class="size-5 mr-2 animate-spin"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M15.312 11.424a5.5 5.5 0 01-9.201 2.466l-.312-.311h2.433a.75.75 0 000-1.5H3.989a.75.75 0 00-.75.75v4.242a.75.75 0 001.5 0v-2.43l.31.31a7 7 0 0011.712-3.138.75.75 0 00-1.449-.39zm1.23-3.723a.75.75 0 00.219-.53V2.929a.75.75 0 00-1.5 0V5.36l-.31-.31A7 7 0 003.239 8.188a.75.75 0 101.448.389A5.5 5.5 0 0113.89 6.11l.311.31h-2.432a.75.75 0 000 1.5h4.243a.75.75 0 00.53-.219z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <span v-if="form.processing">
                                {{ provider ? 'Saving...' : 'Creating...' }}
                            </span>
                            <span v-else>
                                {{ provider ? 'Save Changes' : 'Create Profile' }}
                            </span>
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            as="a"
                            :href="provider ? '/settings/provider' : '/provider/onboarding'"
                            :disabled="form.processing"
                            class="w-full sm:w-auto sm:flex-1"
                        >
                            Cancel
                        </Button>
                    </CardFooter>
                </form>
            </Card>
        </div>
    </AppLayout>
</template>
