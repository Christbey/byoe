<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { PaginatedResponse, Provider } from '@/types/marketplace';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import Badge from '@/components/ui/badge/Badge.vue';

interface Props {
    providers: PaginatedResponse<Provider>;
    filters: {
        status?: string;
        search?: string;
        risk?: string;
    };
    counts: Record<string, number>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Provider Trust',
        href: '/admin/providers',
    },
];

const searchQuery = ref(props.filters.search ?? '');
const statusFilter = ref(props.filters.status ?? 'all');
const riskFilter = ref(props.filters.risk ?? 'all');
const expandedProviderId = ref<number | null>(null);

const reviewForm = useForm({
    vetting_status: 'approved',
    background_check_status: 'clear',
    trust_notes: '',
});

const countsCards = computed(() => [
    { key: 'pending_review', label: 'Pending Review' },
    { key: 'approved', label: 'Approved' },
    { key: 'needs_attention', label: 'Needs Attention' },
    { key: 'suspended', label: 'Suspended' },
]);

const runSearch = () => {
    router.get(
        '/admin/providers',
        {
            search: searchQuery.value,
            status: statusFilter.value,
            risk: riskFilter.value,
        },
        { preserveState: true, preserveScroll: true },
    );
};

const openReview = (provider: Provider) => {
    expandedProviderId.value = provider.id;
    reviewForm.vetting_status = provider.vetting_status;
    reviewForm.background_check_status = provider.background_check_status;
    reviewForm.trust_notes = provider.trust_notes ?? '';
};

const closeReview = () => {
    expandedProviderId.value = null;
    reviewForm.reset();
};

const submitReview = (providerId: number) => {
    reviewForm.post(`/admin/providers/${providerId}/review`, {
        preserveScroll: true,
        onSuccess: () => closeReview(),
    });
};

const statusVariant = (status: string) => {
    const map: Record<string, string> = {
        pending_review: 'warning',
        approved: 'success',
        needs_attention: 'destructive',
        suspended: 'secondary',
        pending: 'warning',
        clear: 'success',
        flagged: 'destructive',
        expired: 'secondary',
        elite: 'success',
        trusted: 'default',
        standard: 'outline',
        at_risk: 'destructive',
    };

    return map[status] ?? 'outline';
};
</script>

<template>
    <Head title="Provider Trust" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-5">
            <section class="ios-surface px-6 py-7 md:px-8">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-muted-foreground">
                    Trust operations
                </p>
                <div class="mt-3 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h1 class="text-3xl font-semibold tracking-[-0.05em] md:text-4xl">
                            Review provider quality before it becomes a marketplace problem.
                        </h1>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-muted-foreground md:text-base">
                            Vet providers, monitor trust and reliability scores, and intervene early when bookings, disputes, or verification signals drift.
                        </p>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <div v-for="card in countsCards" :key="card.key" class="ios-panel px-4 py-4">
                            <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">{{ card.label }}</p>
                            <p class="mt-2 text-2xl font-semibold tracking-[-0.04em]">
                                {{ counts[card.key] ?? 0 }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="flex flex-col gap-3 xl:flex-row">
                <Input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search by provider name or email"
                    class="flex-1"
                    @keydown.enter="runSearch"
                />
                <select
                    v-model="statusFilter"
                    class="h-12 rounded-[20px] border border-white/50 bg-white/76 px-4 text-sm shadow-[0_10px_28px_-20px_rgba(15,23,42,0.25)] backdrop-blur-md dark:border-white/8 dark:bg-white/8"
                >
                    <option value="all">All statuses</option>
                    <option value="pending_review">Pending review</option>
                    <option value="approved">Approved</option>
                    <option value="needs_attention">Needs attention</option>
                    <option value="suspended">Suspended</option>
                </select>
                <select
                    v-model="riskFilter"
                    class="h-12 rounded-[20px] border border-white/50 bg-white/76 px-4 text-sm shadow-[0_10px_28px_-20px_rgba(15,23,42,0.25)] backdrop-blur-md dark:border-white/8 dark:bg-white/8"
                >
                    <option value="all">All risk levels</option>
                    <option value="attention">Needs intervention</option>
                </select>
                <Button @click="runSearch">Apply filters</Button>
            </section>

            <section v-if="providers.data.length > 0" class="space-y-4">
                <Card v-for="provider in providers.data" :key="provider.id">
                    <CardContent class="space-y-5 px-6 py-6">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-xl font-semibold tracking-[-0.03em]">
                                        {{ provider.user?.name ?? 'Provider' }}
                                    </h2>
                                    <Badge :variant="statusVariant(provider.vetting_status)">
                                        {{ provider.vetting_status.replace('_', ' ') }}
                                    </Badge>
                                    <Badge :variant="statusVariant(provider.background_check_status)">
                                        Background {{ provider.background_check_status }}
                                    </Badge>
                                    <Badge :variant="statusVariant(provider.trust_tier ?? 'standard')">
                                        {{ (provider.trust_tier ?? 'standard').replace('_', ' ') }}
                                    </Badge>
                                </div>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    {{ provider.user?.email }}
                                </p>
                                <p class="mt-2 text-sm leading-6 text-muted-foreground">
                                    {{ provider.bio || 'No provider bio on file yet.' }}
                                </p>
                            </div>

                            <div class="grid min-w-full gap-3 sm:grid-cols-4 xl:min-w-[28rem]">
                                <div class="ios-panel px-4 py-3">
                                    <p class="text-[11px] uppercase tracking-[0.18em] text-muted-foreground">Trust</p>
                                    <p class="mt-2 text-xl font-semibold">{{ provider.trust_score }}</p>
                                </div>
                                <div class="ios-panel px-4 py-3">
                                    <p class="text-[11px] uppercase tracking-[0.18em] text-muted-foreground">Reliability</p>
                                    <p class="mt-2 text-xl font-semibold">{{ provider.reliability_score }}</p>
                                </div>
                                <div class="ios-panel px-4 py-3">
                                    <p class="text-[11px] uppercase tracking-[0.18em] text-muted-foreground">Cancel rate</p>
                                    <p class="mt-2 text-xl font-semibold">{{ provider.cancellation_rate.toFixed(1) }}%</p>
                                </div>
                                <div class="ios-panel px-4 py-3">
                                    <p class="text-[11px] uppercase tracking-[0.18em] text-muted-foreground">Disputes</p>
                                    <p class="mt-2 text-xl font-semibold">{{ provider.dispute_count }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-3 md:grid-cols-4">
                            <div class="rounded-[20px] bg-muted/50 px-4 py-3">
                                <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Ratings</p>
                                <p class="mt-1 text-sm font-medium">
                                    {{ provider.average_rating.toFixed(1) }} from {{ provider.total_ratings }}
                                </p>
                            </div>
                            <div class="rounded-[20px] bg-muted/50 px-4 py-3">
                                <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Bookings</p>
                                <p class="mt-1 text-sm font-medium">
                                    {{ provider.completed_without_issue_count }} clean / {{ provider.completed_bookings }} complete
                                </p>
                            </div>
                            <div class="rounded-[20px] bg-muted/50 px-4 py-3">
                                <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">No-shows</p>
                                <p class="mt-1 text-sm font-medium">
                                    {{ provider.no_show_count }}
                                </p>
                            </div>
                            <div class="rounded-[20px] bg-muted/50 px-4 py-3">
                                <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Identity</p>
                                <p class="mt-1 text-sm font-medium">
                                    {{ provider.identity_verified_at ? 'Verified' : 'Not verified' }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <Button variant="outline" @click="openReview(provider)">
                                Review trust
                            </Button>
                            <Button
                                v-if="provider.vetting_status !== 'approved'"
                                variant="ghost"
                                @click="openReview(provider); reviewForm.vetting_status = 'approved'; reviewForm.background_check_status = 'clear';"
                            >
                                Approve
                            </Button>
                            <Button
                                v-if="provider.vetting_status !== 'needs_attention'"
                                variant="ghost"
                                @click="openReview(provider); reviewForm.vetting_status = 'needs_attention';"
                            >
                                Flag
                            </Button>
                            <Button
                                v-if="provider.vetting_status !== 'suspended'"
                                variant="destructive"
                                @click="openReview(provider); reviewForm.vetting_status = 'suspended';"
                            >
                                Suspend
                            </Button>
                        </div>

                        <div
                            v-if="expandedProviderId === provider.id"
                            class="rounded-[24px] border border-white/45 bg-white/62 p-5 backdrop-blur-xl dark:border-white/8 dark:bg-white/6"
                        >
                            <div class="grid gap-4 lg:grid-cols-2">
                                <div class="space-y-2">
                                    <Label for="vetting_status">Vetting status</Label>
                                    <select
                                        id="vetting_status"
                                        v-model="reviewForm.vetting_status"
                                        class="h-12 w-full rounded-[18px] border border-white/50 bg-white/76 px-4 text-sm shadow-[0_10px_28px_-20px_rgba(15,23,42,0.25)] backdrop-blur-md dark:border-white/8 dark:bg-white/8"
                                    >
                                        <option value="pending_review">Pending review</option>
                                        <option value="approved">Approved</option>
                                        <option value="needs_attention">Needs attention</option>
                                        <option value="suspended">Suspended</option>
                                    </select>
                                </div>

                                <div class="space-y-2">
                                    <Label for="background_check_status">Background check</Label>
                                    <select
                                        id="background_check_status"
                                        v-model="reviewForm.background_check_status"
                                        class="h-12 w-full rounded-[18px] border border-white/50 bg-white/76 px-4 text-sm shadow-[0_10px_28px_-20px_rgba(15,23,42,0.25)] backdrop-blur-md dark:border-white/8 dark:bg-white/8"
                                    >
                                        <option value="pending">Pending</option>
                                        <option value="clear">Clear</option>
                                        <option value="flagged">Flagged</option>
                                        <option value="expired">Expired</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-4 space-y-2">
                                <Label for="trust_notes">Review notes</Label>
                                <textarea
                                    id="trust_notes"
                                    v-model="reviewForm.trust_notes"
                                    rows="4"
                                    class="w-full rounded-[20px] border border-white/50 bg-white/76 px-4 py-3 text-sm shadow-[0_10px_28px_-20px_rgba(15,23,42,0.25)] backdrop-blur-md outline-none focus-visible:ring-4 focus-visible:ring-ring/30 dark:border-white/8 dark:bg-white/8"
                                />
                            </div>

                            <div class="mt-4 flex flex-wrap gap-3">
                                <Button @click="submitReview(provider.id)" :disabled="reviewForm.processing">
                                    Save review
                                </Button>
                                <Button variant="outline" @click="closeReview">
                                    Cancel
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </section>

            <Card v-else>
                <CardHeader>
                    <CardTitle>No providers found</CardTitle>
                    <CardDescription>
                        Try loosening the filters or wait for new provider applications.
                    </CardDescription>
                </CardHeader>
            </Card>
        </div>
    </AppLayout>
</template>
