<script setup lang="ts">
import { ref } from 'vue';
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { store } from '@/routes/register';

const props = withDefaults(defineProps<{ preselectedRole?: string | null }>(), {
    preselectedRole: null,
});

const selectedRole = ref<'shop_owner' | 'provider' | null>(
    props.preselectedRole === 'shop_owner' || props.preselectedRole === 'provider'
        ? props.preselectedRole
        : null,
);

const roleLabels: Record<string, string> = {
    shop_owner: '🏪 Shop Owner',
    provider: '💼 Contractor',
};
</script>

<template>
    <AuthBase
        :title="selectedRole ? `Create your ${selectedRole === 'shop_owner' ? 'shop' : 'contractor'} account` : 'Create an account'"
        description="Enter your details below to create your account"
    >
        <Head title="Register" />

        <Form
            v-bind="store.form()"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <!-- Role hidden when pre-selected from landing page -->
            <input v-if="selectedRole" type="hidden" name="role" :value="selectedRole" />

            <!-- Role picker shown when arriving at /register directly -->
            <div v-else class="grid gap-2">
                <Label>I am registering as a…</Label>
                <div class="grid grid-cols-2 gap-3">
                    <label
                        class="flex cursor-pointer flex-col gap-1.5 rounded-lg border p-4 transition-colors"
                        :class="selectedRole === 'shop_owner' ? 'border-primary bg-primary/5' : 'border-border hover:border-primary/50'"
                    >
                        <input
                            type="radio"
                            name="role"
                            value="shop_owner"
                            class="sr-only"
                            @change="selectedRole = 'shop_owner'"
                        />
                        <span class="text-lg">🏪</span>
                        <span class="text-sm font-semibold">Shop Owner</span>
                        <span class="text-xs text-muted-foreground leading-snug">I need to hire contractors for shifts</span>
                    </label>

                    <label
                        class="flex cursor-pointer flex-col gap-1.5 rounded-lg border p-4 transition-colors"
                        :class="selectedRole === 'provider' ? 'border-primary bg-primary/5' : 'border-border hover:border-primary/50'"
                    >
                        <input
                            type="radio"
                            name="role"
                            value="provider"
                            class="sr-only"
                            @change="selectedRole = 'provider'"
                        />
                        <span class="text-lg">💼</span>
                        <span class="text-sm font-semibold">Contractor</span>
                        <span class="text-xs text-muted-foreground leading-snug">I want to pick up shifts and get paid</span>
                    </label>
                </div>
                <InputError :message="errors.role" />
            </div>

            <!-- Confirmation badge when role is pre-selected -->
            <div v-if="preselectedRole && selectedRole" class="flex items-center gap-2 rounded-lg border border-primary/30 bg-primary/5 px-4 py-3">
                <span class="text-sm font-medium">{{ roleLabels[selectedRole] }}</span>
                <span class="text-xs text-muted-foreground ml-auto">
                    <a href="/register" class="underline underline-offset-2">Change</a>
                </span>
            </div>

            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input
                        id="name"
                        type="text"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="name"
                        name="name"
                        placeholder="Full name"
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        :tabindex="2"
                        autocomplete="email"
                        name="email"
                        placeholder="email@example.com"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        :tabindex="3"
                        autocomplete="new-password"
                        name="password"
                        placeholder="Password"
                    />
                    <p class="text-xs text-muted-foreground">Must be at least 8 characters</p>
                    <InputError :message="errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm password</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        :tabindex="4"
                        autocomplete="new-password"
                        name="password_confirmation"
                        placeholder="Confirm password"
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>

                <div class="grid gap-2">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input
                            type="checkbox"
                            name="terms"
                            value="1"
                            :tabindex="5"
                            class="mt-0.5 size-4 rounded border-border accent-primary"
                            required
                        />
                        <span class="text-sm text-muted-foreground leading-snug">
                            I agree to the
                            <a href="/legal/terms" target="_blank" class="text-foreground underline underline-offset-2 hover:text-primary">Terms of Service</a>
                            and, if registering as a Contractor, the
                            <a href="/legal/contractor" target="_blank" class="text-foreground underline underline-offset-2 hover:text-primary">Independent Contractor Agreement</a>.
                        </span>
                    </label>
                    <InputError :message="errors.terms" />
                </div>

                <Button
                    type="submit"
                    class="mt-2 w-full"
                    tabindex="6"
                    :disabled="processing || !selectedRole"
                    data-test="register-user-button"
                >
                    <Spinner v-if="processing" />
                    Create account
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Already have an account?
                <TextLink :href="login()" class="underline underline-offset-4" :tabindex="7">Log in</TextLink>
            </div>
        </Form>
    </AuthBase>
</template>
