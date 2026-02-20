<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { Shop, Industry } from '@/types/marketplace';
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

interface Props {
    shop: Shop;
    industries: Industry[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Shop Profile', href: '/settings/profile?tab=shop' },
    { title: 'Edit', href: '/settings/profile?tab=shop' },
];

const form = useForm({
    name: props.shop.name,
    description: props.shop.description || '',
    phone: props.shop.phone || '',
    website: props.shop.website || '',
    operating_hours: props.shop.operating_hours || {},
    industry_id: props.shop.industry_id ?? null,
    custom_skills: props.shop.custom_skills ?? [],
    ein: props.shop.ein || '',
});

const customSkillInput = ref('');

const addCustomSkill = (skill: string) => {
    const trimmed = skill.trim();
    if (trimmed && !form.custom_skills.includes(trimmed)) {
        form.custom_skills.push(trimmed);
        customSkillInput.value = '';
    }
};

const removeCustomSkill = (skill: string) => {
    const index = form.custom_skills.indexOf(skill);
    if (index > -1) form.custom_skills.splice(index, 1);
};

const handleSubmit = () => {
    form.put('/shop/profile', {
        preserveScroll: true,
    });
};

const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
        active: 'default',
        inactive: 'secondary',
        suspended: 'destructive',
    };
    return colors[status] || 'outline';
};

const getStatusLabel = (status: string) => {
    return status.charAt(0).toUpperCase() + status.slice(1);
};
</script>

<template>
    <Head title="Edit Shop Profile" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6 max-w-3xl">
            <!-- Header -->
            <div class="space-y-2">
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                        Shop Profile
                    </h1>
                    <Badge :variant="getStatusColor(shop.status)">
                        {{ getStatusLabel(shop.status) }}
                    </Badge>
                </div>
                <p class="text-sm text-muted-foreground md:text-base">
                    Manage your shop information and settings
                </p>
            </div>

            <!-- Profile Form -->
            <Card>
                <form @submit.prevent="handleSubmit">
                    <CardHeader>
                        <CardTitle>Shop Details</CardTitle>
                        <CardDescription>
                            Update your shop's basic information
                        </CardDescription>
                    </CardHeader>

                    <CardContent class="space-y-6">
                        <!-- Shop Name -->
                        <div class="space-y-2">
                            <Label for="name">Shop Name</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                            placeholder="e.g., Main Street Café"
                                maxlength="255"
                            />
                            <p
                                v-if="form.errors.name"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <!-- Description -->
                        <div class="space-y-2">
                            <Label for="description">Description</Label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="4"
                                placeholder="Tell us about your business..."
                                class="w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm resize-y"
                            ></textarea>
                            <p
                                v-if="form.errors.description"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.description }}
                            </p>
                        </div>

                        <!-- Phone -->
                        <div class="space-y-2">
                            <Label for="phone">Phone Number</Label>
                            <Input
                                id="phone"
                                v-model="form.phone"
                                type="tel"
                                placeholder="(555) 123-4567"
                                maxlength="20"
                            />
                            <p
                                v-if="form.errors.phone"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.phone }}
                            </p>
                        </div>

                        <!-- Website -->
                        <div class="space-y-2">
                            <Label for="website">Website</Label>
                            <Input
                                id="website"
                                v-model="form.website"
                                type="url"
                                placeholder="https://example.com"
                                maxlength="255"
                            />
                            <p
                                v-if="form.errors.website"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.website }}
                            </p>
                        </div>

                        <!-- EIN -->
                        <div class="space-y-2">
                            <Label for="ein">Employer Identification Number (EIN)</Label>
                            <Input
                                id="ein"
                                v-model="form.ein"
                                type="text"
                                placeholder="12-3456789"
                                maxlength="10"
                            />
                            <p v-if="form.errors.ein" class="text-sm text-destructive">{{ form.errors.ein }}</p>
                            <p class="text-xs text-muted-foreground">Optional. Format: XX-XXXXXXX. Stored securely and encrypted.</p>
                        </div>

                        <!-- Industry -->
                        <div class="space-y-2">
                            <Label for="industry_id">Industry</Label>
                            <select
                                id="industry_id"
                                v-model="form.industry_id"
                                class="w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] md:text-sm"
                            >
                                <option :value="null">— Select an industry —</option>
                                <option v-for="industry in industries" :key="industry.id" :value="industry.id">
                                    {{ industry.name }}
                                </option>
                            </select>
                            <p v-if="form.errors.industry_id" class="text-sm text-destructive">{{ form.errors.industry_id }}</p>
                            <p class="text-xs text-muted-foreground">Sets the default skills shown when creating a service request.</p>
                        </div>

                        <!-- Custom Skills -->
                        <div class="space-y-2">
                            <Label>Custom Skills</Label>
                            <p class="text-xs text-muted-foreground">Add skills specific to your shop that go beyond your industry's defaults.</p>
                            <div class="flex gap-2">
                                <Input
                                    v-model="customSkillInput"
                                    type="text"
                                    placeholder="e.g., Spanish-speaking"
                                    @keydown.enter.prevent="addCustomSkill(customSkillInput)"
                                    class="flex-1"
                                />
                                <Button type="button" variant="secondary" @click="addCustomSkill(customSkillInput)">Add</Button>
                            </div>
                            <div v-if="form.custom_skills.length > 0" class="flex flex-wrap gap-2">
                                <Badge
                                    v-for="skill in form.custom_skills"
                                    :key="skill"
                                    variant="secondary"
                                    as="button"
                                    type="button"
                                    @click="removeCustomSkill(skill)"
                                    class="cursor-pointer hover:opacity-80 transition-opacity"
                                >
                                    {{ skill }}
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3 ml-1">
                                        <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                                    </svg>
                                </Badge>
                            </div>
                            <p v-if="form.errors.custom_skills" class="text-sm text-destructive">{{ form.errors.custom_skills }}</p>
                        </div>

                        <!-- Operating Hours Note -->
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
                                        Operating Hours
                                    </p>
                                    <p class="text-sm text-muted-foreground">
                                        Operating hours management will be
                                        available in a future update. For now,
                                        this information is stored in JSON
                                        format.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Status Information -->
                        <div class="rounded-lg border border-border bg-muted/50 p-4">
                            <div class="space-y-2">
                                <p class="text-sm font-medium">
                                    Account Status
                                </p>
                                <div class="flex items-center gap-2">
                                    <Badge :variant="getStatusColor(shop.status)">
                                        {{ getStatusLabel(shop.status) }}
                                    </Badge>
                                    <p class="text-sm text-muted-foreground">
                                        <span v-if="shop.status === 'active'">
                                            Your shop is active and visible to
                                            providers
                                        </span>
                                        <span
                                            v-else-if="shop.status === 'inactive'"
                                        >
                                            Your shop is inactive and not
                                            visible to providers
                                        </span>
                                        <span v-else>
                                            Your shop has been suspended.
                                            Contact support for assistance.
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>

                    <CardFooter class="flex flex-col gap-3 sm:flex-row">
                        <Button
                            type="submit"
                            :disabled="form.processing || !form.isDirty"
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
                            <span v-if="form.processing">Saving...</span>
                            <span v-else>Save Changes</span>
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            as="a"
                            href="/settings/profile?tab=shop"
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
