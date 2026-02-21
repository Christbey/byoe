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
import Icon from '@/components/ui/icon/Icon.vue';
import type { BreadcrumbItem } from '@/types';

interface Industry {
    id: number;
    name: string;
}

interface Props {
    industries: Industry[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Shop Onboarding', href: '/shop/profile/edit' },
];

const currentStep = ref(1);
const totalSteps = 2;

const shopForm = useForm({
    industry_id: null as number | null,
    name: '',
    description: '',
    phone: '',
    website: '',
    ein: '',
});

const locationForm = useForm({
    name: '',
    address_line1: '',
    address_line2: '',
    city: '',
    state: '',
    zip_code: '',
    operating_hours: {
        monday: { open: '09:00', close: '17:00', is_open: true },
        tuesday: { open: '09:00', close: '17:00', is_open: true },
        wednesday: { open: '09:00', close: '17:00', is_open: true },
        thursday: { open: '09:00', close: '17:00', is_open: true },
        friday: { open: '09:00', close: '17:00', is_open: true },
        saturday: { open: '09:00', close: '17:00', is_open: false },
        sunday: { open: '09:00', close: '17:00', is_open: false },
    } as Record<string, { open: string; close: string; is_open: boolean }>,
    is_primary: true,
});

const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

const canProceedToStep2 = computed(() => {
    return shopForm.industry_id && shopForm.name.trim().length > 0;
});

const submitShopProfile = () => {
    shopForm.put('/shop/profile', {
        preserveScroll: true,
        onSuccess: () => {
            // Shop created, move to location step
            currentStep.value = 2;
        },
    });
};

const submitLocation = () => {
    locationForm.post('/shop/locations', {
        preserveScroll: true,
        onSuccess: () => {
            // Location created, onboarding complete
            router.visit('/shop/dashboard');
        },
    });
};

const previousStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};
</script>

<template>
    <Head title="Shop Onboarding" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 max-w-4xl mx-auto">
            <!-- Header -->
            <div class="space-y-3 text-center">
                <div class="flex justify-center">
                    <div class="rounded-full bg-primary/10 p-3">
                        <Icon name="Store" class="size-8 text-primary" />
                    </div>
                </div>
                <h1 class="text-3xl font-bold tracking-tight md:text-4xl">
                    Welcome to ShiftFinder
                </h1>
                <p class="text-base text-muted-foreground md:text-lg max-w-2xl mx-auto">
                    Let's set up your shop to start finding skilled contractors
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
                                <p class="font-semibold text-sm">Shop Profile</p>
                                <p class="text-xs text-muted-foreground">Basic shop information</p>
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
                                <p class="font-semibold text-sm">Primary Location</p>
                                <p class="text-xs text-muted-foreground">Address & operating hours</p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Step 1: Shop Profile -->
            <Card v-show="currentStep === 1">
                <CardHeader>
                    <CardTitle>Step 1: Shop Profile</CardTitle>
                    <CardDescription>
                        Tell us about your business
                    </CardDescription>
                </CardHeader>

                <CardContent class="space-y-6">
                    <!-- Shop Name -->
                    <div class="space-y-2">
                        <Label for="shop_name">
                            Shop Name <span class="text-destructive">*</span>
                        </Label>
                        <Input
                            id="shop_name"
                            v-model="shopForm.name"
                            type="text"
                            placeholder="e.g., Downtown Coffee Co."
                            required
                        />
                        <p v-if="shopForm.errors.name" class="text-sm text-destructive">
                            {{ shopForm.errors.name }}
                        </p>
                    </div>

                    <!-- Industry -->
                    <div class="space-y-2">
                        <Label for="industry_id">
                            Industry <span class="text-destructive">*</span>
                        </Label>
                        <select
                            id="industry_id"
                            v-model="shopForm.industry_id"
                            required
                            class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                        >
                            <option :value="null" disabled>Select your industry...</option>
                            <option v-for="industry in industries" :key="industry.id" :value="industry.id">
                                {{ industry.name }}
                            </option>
                        </select>
                        <p v-if="shopForm.errors.industry_id" class="text-sm text-destructive">
                            {{ shopForm.errors.industry_id }}
                        </p>
                    </div>

                    <!-- Description -->
                    <div class="space-y-2">
                        <Label for="description">Description (Optional)</Label>
                        <textarea
                            id="description"
                            v-model="shopForm.description"
                            rows="4"
                            placeholder="Tell contractors about your shop..."
                            class="w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm resize-y"
                        ></textarea>
                    </div>

                    <!-- Phone -->
                    <div class="space-y-2">
                        <Label for="phone">Phone (Optional)</Label>
                        <Input
                            id="phone"
                            v-model="shopForm.phone"
                            type="tel"
                            placeholder="(555) 123-4567"
                        />
                    </div>

                    <!-- Website -->
                    <div class="space-y-2">
                        <Label for="website">Website (Optional)</Label>
                        <Input
                            id="website"
                            v-model="shopForm.website"
                            type="url"
                            placeholder="https://example.com"
                        />
                    </div>

                    <!-- EIN -->
                    <div class="space-y-2">
                        <Label for="ein">EIN (Optional)</Label>
                        <Input
                            id="ein"
                            v-model="shopForm.ein"
                            type="text"
                            placeholder="12-3456789"
                        />
                        <p class="text-xs text-muted-foreground">
                            Your Employer Identification Number for tax purposes
                        </p>
                    </div>
                </CardContent>

                <CardFooter class="bg-muted/30">
                    <Button
                        @click="submitShopProfile"
                        :disabled="shopForm.processing || !canProceedToStep2"
                        class="w-full"
                    >
                        <Icon v-if="shopForm.processing" name="Loader2" class="size-4 mr-2 animate-spin" />
                        {{ shopForm.processing ? 'Saving...' : 'Save & Continue' }}
                        <Icon v-if="!shopForm.processing" name="ArrowRight" size="sm" class="ml-2" />
                    </Button>
                </CardFooter>
            </Card>

            <!-- Step 2: Primary Location -->
            <Card v-show="currentStep === 2">
                <CardHeader>
                    <CardTitle>Step 2: Primary Location</CardTitle>
                    <CardDescription>
                        Add your main shop location
                    </CardDescription>
                </CardHeader>

                <CardContent class="space-y-6">
                    <!-- Location Name -->
                    <div class="space-y-2">
                        <Label for="location_name">
                            Location Name <span class="text-destructive">*</span>
                        </Label>
                        <Input
                            id="location_name"
                            v-model="locationForm.name"
                            type="text"
                            placeholder="e.g., Main Street Location"
                            required
                        />
                    </div>

                    <!-- Address -->
                    <div class="space-y-4">
                        <Label>Address <span class="text-destructive">*</span></Label>

                        <Input
                            v-model="locationForm.address_line1"
                            type="text"
                            placeholder="Street address"
                            required
                        />

                        <Input
                            v-model="locationForm.address_line2"
                            type="text"
                            placeholder="Apt, suite, etc. (optional)"
                        />

                        <div class="grid grid-cols-2 gap-4">
                            <Input
                                v-model="locationForm.city"
                                type="text"
                                placeholder="City"
                                required
                            />

                            <Input
                                v-model="locationForm.state"
                                type="text"
                                placeholder="State"
                                required
                                maxlength="2"
                            />
                        </div>

                        <Input
                            v-model="locationForm.zip_code"
                            type="text"
                            placeholder="ZIP code"
                            required
                            class="w-48"
                        />
                    </div>

                    <!-- Operating Hours -->
                    <div class="space-y-3">
                        <Label>Operating Hours</Label>
                        <div class="space-y-2">
                            <div v-for="day in days" :key="day" class="flex items-center gap-3">
                                <button
                                    type="button"
                                    @click="locationForm.operating_hours[day].is_open = !locationForm.operating_hours[day].is_open"
                                    class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                                    :class="locationForm.operating_hours[day].is_open ? 'bg-primary' : 'bg-muted'"
                                >
                                    <span
                                        class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition-transform"
                                        :class="locationForm.operating_hours[day].is_open ? 'translate-x-4' : 'translate-x-0'"
                                    />
                                </button>

                                <span class="w-24 text-sm font-medium capitalize">{{ day }}</span>

                                <template v-if="locationForm.operating_hours[day].is_open">
                                    <input
                                        type="time"
                                        v-model="locationForm.operating_hours[day].open"
                                        class="rounded border border-input bg-background px-2 py-1 text-sm"
                                    />
                                    <span class="text-sm text-muted-foreground">to</span>
                                    <input
                                        type="time"
                                        v-model="locationForm.operating_hours[day].close"
                                        class="rounded border border-input bg-background px-2 py-1 text-sm"
                                    />
                                </template>
                                <span v-else class="text-sm text-muted-foreground">Closed</span>
                            </div>
                        </div>
                    </div>
                </CardContent>

                <CardFooter class="flex gap-3 bg-muted/30">
                    <Button @click="previousStep" variant="outline">
                        <Icon name="ArrowLeft" size="sm" class="mr-2" />
                        Back
                    </Button>
                    <Button
                        @click="submitLocation"
                        :disabled="locationForm.processing"
                        class="flex-1"
                    >
                        <Icon v-if="locationForm.processing" name="Loader2" class="size-4 mr-2 animate-spin" />
                        {{ locationForm.processing ? 'Creating...' : 'Complete Setup' }}
                        <Icon v-if="!locationForm.processing" name="Check" size="sm" class="ml-2" />
                    </Button>
                </CardFooter>
            </Card>
        </div>
    </AppLayout>
</template>
