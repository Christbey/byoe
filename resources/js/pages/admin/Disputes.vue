<script setup lang="ts">
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { PaginatedResponse } from '@/types/marketplace';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardFooter from '@/components/ui/card/CardFooter.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import Badge from '@/components/ui/badge/Badge.vue';

interface Dispute {
    id: number;
    booking_id: number;
    filed_by_user_id: number;
    against_user_id: number;
    type: string;
    description: string;
    status: 'open' | 'under_review' | 'resolved' | 'closed';
    resolution_notes?: string;
    created_at: string;
    updated_at: string;
    filed_by?: { name: string; email: string };
    against?: { name: string; email: string };
}

interface Props {
    disputes: PaginatedResponse<Dispute>;
    filter?: string;
}

const props = withDefaults(defineProps<Props>(), {
    filter: 'open',
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Disputes',
        href: '/admin/disputes',
    },
];

const activeFilter = ref(props.filter);
const resolvingDisputeId = ref<number | null>(null);

const filters = [
    { key: 'open', label: 'Open' },
    { key: 'under_review', label: 'Under Review' },
    { key: 'resolved', label: 'Resolved' },
    { key: 'closed', label: 'Closed' },
];

const resolutionForm = useForm({
    notes: '',
    status: 'resolved' as 'resolved' | 'closed',
});

const handleFilterChange = (filterKey: string) => {
    activeFilter.value = filterKey;
    router.get(
        '/admin/disputes',
        { filter: filterKey },
        { preserveState: true, preserveScroll: true },
    );
};

const handleResolveDispute = (disputeId: number) => {
    resolvingDisputeId.value = disputeId;
    resolutionForm.notes = '';
};

const handleSubmitResolution = (disputeId: number) => {
    resolutionForm.post(`/admin/disputes/${disputeId}/resolve`, {
        preserveScroll: true,
        onSuccess: () => {
            resolvingDisputeId.value = null;
            resolutionForm.reset();
        },
    });
};

const formatDate = (date: string) => {
    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    }).format(new Date(date));
};

const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
        open: 'destructive',
        under_review: 'warning',
        resolved: 'success',
        closed: 'outline',
    };
    return colors[status] || 'outline';
};
</script>

<template>
    <Head title="Disputes" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
            <!-- Header -->
            <div class="space-y-2">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                    Dispute Management
                </h1>
                <p class="text-sm text-muted-foreground md:text-base">
                    Review and resolve user disputes
                </p>
            </div>

            <!-- Filter Tabs -->
            <div class="flex gap-2 overflow-x-auto pb-2 -mx-4 px-4 md:mx-0 md:px-0">
                <Badge
                    v-for="filter in filters"
                    :key="filter.key"
                    :variant="activeFilter === filter.key ? 'default' : 'outline'"
                    as="button"
                    @click="handleFilterChange(filter.key)"
                    class="cursor-pointer whitespace-nowrap px-4 py-2 text-sm font-medium transition-colors touch-manipulation"
                >
                    {{ filter.label }}
                </Badge>
            </div>

            <!-- Disputes List -->
            <div v-if="disputes.data.length > 0" class="space-y-4">
                <Card v-for="dispute in disputes.data" :key="dispute.id">
                    <CardHeader>
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <CardTitle class="text-lg">
                                    Dispute #{{ dispute.id }}
                                </CardTitle>
                                <div class="flex items-center gap-2 mt-1">
                                    <Badge variant="outline">{{ dispute.type }}</Badge>
                                    <Badge :variant="getStatusColor(dispute.status)">
                                        {{ dispute.status.replace('_', ' ') }}
                                    </Badge>
                                </div>
                            </div>
                            <p class="text-sm text-muted-foreground shrink-0">
                                {{ formatDate(dispute.created_at) }}
                            </p>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Parties Involved -->
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="p-3 bg-muted/50 rounded-lg">
                                <p class="text-xs text-muted-foreground mb-1">Filed By</p>
                                <p class="font-medium text-sm">{{ dispute.filed_by?.name }}</p>
                                <p class="text-xs text-muted-foreground">{{ dispute.filed_by?.email }}</p>
                            </div>
                            <div class="p-3 bg-muted/50 rounded-lg">
                                <p class="text-xs text-muted-foreground mb-1">Against</p>
                                <p class="font-medium text-sm">{{ dispute.against?.name }}</p>
                                <p class="text-xs text-muted-foreground">{{ dispute.against?.email }}</p>
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <p class="text-sm font-medium mb-2">Description</p>
                            <p class="text-sm text-muted-foreground">{{ dispute.description }}</p>
                        </div>

                        <!-- Booking Reference -->
                        <div class="text-sm">
                            <span class="text-muted-foreground">Booking:</span>
                            <a :href="`/admin/bookings/${dispute.booking_id}`" class="ml-2 text-primary hover:underline">
                                #{{ dispute.booking_id }}
                            </a>
                        </div>

                        <!-- Resolution Notes (if resolved) -->
                        <div v-if="dispute.resolution_notes" class="p-3 bg-green-500/10 border border-green-500/20 rounded-lg">
                            <p class="text-sm font-medium mb-1">Resolution Notes</p>
                            <p class="text-sm text-muted-foreground">{{ dispute.resolution_notes }}</p>
                        </div>

                        <!-- Resolve Form -->
                        <div
                            v-if="resolvingDisputeId === dispute.id"
                            class="p-4 bg-muted/50 rounded-lg space-y-3"
                        >
                            <div class="space-y-2">
                                <Label for="resolution_notes">Resolution Notes</Label>
                                <textarea
                                    id="resolution_notes"
                                    v-model="resolutionForm.notes"
                                    rows="4"
                                    required
                                    placeholder="Explain how this dispute was resolved..."
                                    class="w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm resize-y"
                                ></textarea>
                            </div>
                            <div class="space-y-2">
                                <Label for="status">New Status</Label>
                                <select
                                    id="status"
                                    v-model="resolutionForm.status"
                                    class="w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] md:text-sm"
                                >
                                    <option value="resolved">Resolved</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                            <div class="flex gap-2">
                                <Button
                                    @click="handleSubmitResolution(dispute.id)"
                                    :disabled="resolutionForm.processing"
                                    size="sm"
                                >
                                    Submit Resolution
                                </Button>
                                <Button
                                    @click="resolvingDisputeId = null"
                                    variant="ghost"
                                    size="sm"
                                >
                                    Cancel
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                    <CardFooter v-if="dispute.status === 'open' || dispute.status === 'under_review'" class="flex gap-2">
                        <Button
                            v-if="resolvingDisputeId !== dispute.id"
                            @click="handleResolveDispute(dispute.id)"
                            size="sm"
                        >
                            Resolve Dispute
                        </Button>
                        <Button as="a" :href="`/admin/disputes/${dispute.id}`" variant="outline" size="sm">
                            View Details
                        </Button>
                    </CardFooter>
                </Card>
            </div>

            <!-- Empty State -->
            <Card v-else>
                <CardContent class="flex flex-col items-center justify-center py-12 px-4 text-center">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                        class="size-16 text-muted-foreground mb-4"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    <h3 class="text-lg font-semibold mb-2">No disputes found</h3>
                    <p class="text-sm text-muted-foreground max-w-md">
                        There are no disputes in this category.
                    </p>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div v-if="disputes.data.length > 0" class="text-center text-sm text-muted-foreground pb-4">
                Page {{ disputes.current_page }} of {{ disputes.last_page }}
            </div>
        </div>
    </AppLayout>
</template>
