<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { ShopLocation } from '@/types/marketplace';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import InputError from '@/components/InputError.vue';

interface Props {
    location: ShopLocation;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Shop Locations',
        href: '/shop/locations',
    },
    {
        title: 'Edit Location',
        href: `/shop/locations/${props.location.id}/edit`,
    },
];

const form = useForm({
    address_line_1: props.location.address_line_1,
    address_line_2: props.location.address_line_2 || '',
    city: props.location.city,
    state: props.location.state,
    zip_code: props.location.zip_code,
    is_primary: props.location.is_primary,
});

const submit = () => {
    form.put(`/shop/locations/${props.location.id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Edit Location" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6 max-w-2xl">
            <!-- Header -->
            <div class="space-y-2">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                    Edit Location
                </h1>
                <p class="text-sm text-muted-foreground md:text-base">
                    Update your shop location details
                </p>
            </div>

            <!-- Form -->
            <Card>
                <CardHeader>
                    <CardTitle>Location Details</CardTitle>
                    <CardDescription>
                        Update the address for your shop location. If you change
                        the address, we'll automatically re-geocode it.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Address Line 1 -->
                        <div class="space-y-2">
                            <Label for="address_line_1">
                                Street Address
                                <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="address_line_1"
                                v-model="form.address_line_1"
                                type="text"
                                placeholder="123 Main Street"
                                :disabled="form.processing"
                                required
                            />
                            <InputError :message="form.errors.address_line_1" />
                        </div>

                        <!-- Address Line 2 -->
                        <div class="space-y-2">
                            <Label for="address_line_2">
                                Apartment, Suite, etc. (Optional)
                            </Label>
                            <Input
                                id="address_line_2"
                                v-model="form.address_line_2"
                                type="text"
                                placeholder="Suite 100"
                                :disabled="form.processing"
                            />
                            <InputError :message="form.errors.address_line_2" />
                        </div>

                        <!-- City -->
                        <div class="space-y-2">
                            <Label for="city">
                                City
                                <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="city"
                                v-model="form.city"
                                type="text"
                                placeholder="San Francisco"
                                :disabled="form.processing"
                                required
                            />
                            <InputError :message="form.errors.city" />
                        </div>

                        <!-- State & Zip Code -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="state">
                                    State
                                    <span class="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="state"
                                    v-model="form.state"
                                    type="text"
                                    placeholder="CA"
                                    maxlength="2"
                                    :disabled="form.processing"
                                    required
                                />
                                <InputError :message="form.errors.state" />
                            </div>

                            <div class="space-y-2">
                                <Label for="zip_code">
                                    ZIP Code
                                    <span class="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="zip_code"
                                    v-model="form.zip_code"
                                    type="text"
                                    placeholder="94102"
                                    :disabled="form.processing"
                                    required
                                />
                                <InputError :message="form.errors.zip_code" />
                            </div>
                        </div>

                        <!-- Primary Location -->
                        <div class="flex items-center space-x-2">
                            <Checkbox
                                id="is_primary"
                                :checked="form.is_primary"
                                @update:checked="form.is_primary = $event"
                                :disabled="form.processing"
                            />
                            <Label
                                for="is_primary"
                                class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 cursor-pointer"
                            >
                                Set as primary location
                            </Label>
                        </div>
                        <p class="text-xs text-muted-foreground -mt-4">
                            Your primary location will be used as the default for
                            new service requests.
                        </p>

                        <InputError :message="form.errors.shop" />

                        <!-- Actions -->
                        <div class="flex flex-col sm:flex-row gap-3 pt-4">
                            <Button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full sm:w-auto"
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
                                as="a"
                                href="/shop/locations"
                                variant="outline"
                                :disabled="form.processing"
                                class="w-full sm:w-auto"
                            >
                                Cancel
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
