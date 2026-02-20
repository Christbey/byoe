<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { Certification } from '@/types/marketplace';
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
    certifications: Certification[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Provider Profile', href: '/settings/profile?tab=provider' },
    { title: 'Certifications', href: '/provider/certifications' },
];

const certificationTypes = [
    { key: 'food_handler', label: 'Food Handler Card' },
    { key: 'food_manager', label: 'Food Manager Certification' },
    { key: 'servsafe', label: 'ServSafe Certificate' },
    { key: 'barista', label: 'Barista Certification' },
    { key: 'allergen', label: 'Allergen Awareness' },
    { key: 'other', label: 'Other' },
];

const showForm = ref(false);
const deletingId = ref<string | null>(null);

const form = useForm({
    type: '',
    name: '',
    issued_at: '',
    expires_at: '',
    issuer: '',
});

const handleSubmit = () => {
    form.post('/provider/certifications', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showForm.value = false;
        },
    });
};

const handleDelete = (certId: string) => {
    if (!confirm('Remove this certification?')) return;
    deletingId.value = certId;
    router.delete(`/provider/certifications/${certId}`, {
        preserveScroll: true,
        onFinish: () => { deletingId.value = null; },
    });
};

const formatDate = (date: string | undefined) => {
    if (!date) return null;
    return new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(date + 'T00:00:00'));
};

const isExpired = (expires: string | undefined) => {
    if (!expires) return false;
    return new Date(expires) < new Date();
};

const isExpiringSoon = (expires: string | undefined) => {
    if (!expires) return false;
    const expiry = new Date(expires);
    const soon = new Date();
    soon.setDate(soon.getDate() + 60);
    return expiry > new Date() && expiry < soon;
};

const certTypeLabel = (key: string) =>
    certificationTypes.find(c => c.key === key)?.label ?? key;
</script>

<template>
    <Head title="Certifications" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6 max-w-3xl">
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-2">
                    <h1 class="text-2xl font-bold tracking-tight md:text-3xl">Certifications</h1>
                    <p class="text-sm text-muted-foreground">Manage your food service certifications and credentials</p>
                </div>
                <Button v-if="!showForm" @click="showForm = true">
                    Add Certification
                </Button>
            </div>

            <!-- Add Form -->
                <Card v-if="showForm">
                    <form @submit.prevent="handleSubmit">
                        <CardHeader>
                            <CardTitle>Add New Certification</CardTitle>
                            <CardDescription>Enter details about your certification or credential</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <!-- Type -->
                            <div class="space-y-2">
                                <Label for="cert-type">Certification Type</Label>
                                <select
                                    id="cert-type"
                                    v-model="form.type"
                                    required
                                    class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs focus:outline-none focus:border-ring focus:ring-ring/50 focus:ring-[3px]"
                                >
                                    <option value="">Select type...</option>
                                    <option v-for="t in certificationTypes" :key="t.key" :value="t.key">{{ t.label }}</option>
                                </select>
                                <p v-if="form.errors.type" class="text-sm text-destructive">{{ form.errors.type }}</p>
                            </div>

                            <!-- Name -->
                            <div class="space-y-2">
                                <Label for="cert-name">Certificate Name / Number</Label>
                                <Input
                                    id="cert-name"
                                    v-model="form.name"
                                    placeholder="e.g. Food Handler Card #12345"
                                    required
                                />
                                <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                            </div>

                            <!-- Issuer -->
                            <div class="space-y-2">
                                <Label for="cert-issuer">Issuing Organization</Label>
                                <Input
                                    id="cert-issuer"
                                    v-model="form.issuer"
                                    placeholder="e.g. National Restaurant Association"
                                />
                            </div>

                            <!-- Dates -->
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <Label for="cert-issued">Issue Date</Label>
                                    <Input id="cert-issued" v-model="form.issued_at" type="date" />
                                    <p v-if="form.errors.issued_at" class="text-sm text-destructive">{{ form.errors.issued_at }}</p>
                                </div>
                                <div class="space-y-2">
                                    <Label for="cert-expires">Expiration Date</Label>
                                    <Input id="cert-expires" v-model="form.expires_at" type="date" />
                                    <p v-if="form.errors.expires_at" class="text-sm text-destructive">{{ form.errors.expires_at }}</p>
                                </div>
                            </div>
                        </CardContent>
                        <CardFooter class="flex gap-2">
                            <Button type="submit" :disabled="form.processing">
                                {{ form.processing ? 'Saving...' : 'Save Certification' }}
                            </Button>
                            <Button type="button" variant="outline" @click="showForm = false; form.reset()">
                                Cancel
                            </Button>
                        </CardFooter>
                    </form>
                </Card>

                <!-- Empty State -->
                <Card v-if="certifications.length === 0 && !showForm" class="border-dashed">
                    <CardContent class="py-12 text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-12 mx-auto mb-3 text-muted-foreground">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" />
                        </svg>
                        <h3 class="font-semibold mb-1">No certifications added</h3>
                        <p class="text-sm text-muted-foreground mb-4">Add your food handler card, barista cert, or other credentials</p>
                        <Button @click="showForm = true">Add Your First Certification</Button>
                    </CardContent>
                </Card>

                <!-- Certifications List -->
                <div v-if="certifications.length > 0" class="space-y-3">
                    <Card
                        v-for="cert in certifications"
                        :key="cert.id"
                        :class="isExpired(cert.expires_at) ? 'border-destructive/30' : ''"
                    >
                        <CardContent class="pt-4 pb-4">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1 space-y-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-semibold">{{ cert.name }}</span>
                                        <Badge v-if="isExpired(cert.expires_at)" variant="destructive" class="text-xs">Expired</Badge>
                                        <Badge v-else-if="isExpiringSoon(cert.expires_at)" variant="warning" class="text-xs">Expiring Soon</Badge>
                                        <Badge v-else-if="cert.expires_at" variant="success" class="text-xs">Active</Badge>
                                    </div>
                                    <p class="text-sm text-muted-foreground">{{ certTypeLabel(cert.type) }}</p>
                                    <p v-if="cert.issuer" class="text-xs text-muted-foreground">{{ cert.issuer }}</p>
                                    <div class="flex gap-4 text-xs text-muted-foreground mt-1">
                                        <span v-if="cert.issued_at">Issued: {{ formatDate(cert.issued_at) }}</span>
                                        <span v-if="cert.expires_at" :class="isExpired(cert.expires_at) ? 'text-destructive' : ''">
                                            Expires: {{ formatDate(cert.expires_at) }}
                                        </span>
                                    </div>
                                </div>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    :disabled="deletingId === cert.id"
                                    @click="handleDelete(cert.id)"
                                    class="text-muted-foreground hover:text-destructive"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                                        <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z" clip-rule="evenodd" />
                                    </svg>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
        </div>
    </AppLayout>
</template>
