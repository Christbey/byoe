<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { PaginatedResponse } from '@/types/marketplace';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import Badge from '@/components/ui/badge/Badge.vue';

interface AuditLog {
    id: number;
    user_id?: number;
    action: string;
    resource_type: string;
    resource_id?: number;
    old_values?: Record<string, any>;
    new_values?: Record<string, any>;
    ip_address?: string;
    user_agent?: string;
    created_at: string;
    user?: {
        name: string;
        email: string;
    };
}

interface Props {
    logs: PaginatedResponse<AuditLog>;
    search?: string;
    start_date?: string;
    end_date?: string;
}

const props = withDefaults(defineProps<Props>(), {
    search: '',
    start_date: '',
    end_date: '',
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Audit Logs',
        href: '/admin/audit-logs',
    },
];

const searchQuery = ref(props.search);
const startDate = ref(props.start_date);
const endDate = ref(props.end_date);
const expandedLogId = ref<number | null>(null);

const handleSearch = () => {
    router.get(
        '/admin/audit-logs',
        {
            search: searchQuery.value,
            start_date: startDate.value,
            end_date: endDate.value,
        },
        { preserveState: true, preserveScroll: true },
    );
};

const handleExportCSV = () => {
    window.open('/admin/audit-logs/export', '_blank');
};

const toggleExpanded = (logId: number) => {
    expandedLogId.value = expandedLogId.value === logId ? null : logId;
};

const formatDate = (date: string) => {
    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        second: '2-digit',
    }).format(new Date(date));
};

const getActionColor = (action: string) => {
    const colors: Record<string, string> = {
        create: 'default',
        update: 'secondary',
        delete: 'destructive',
        login: 'default',
        logout: 'outline',
    };
    return colors[action.toLowerCase()] || 'outline';
};
</script>

<template>
    <Head title="Audit Logs" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
            <!-- Header -->
            <div class="flex items-center justify-between gap-4">
                <div class="space-y-2">
                    <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                        Audit Logs
                    </h1>
                    <p class="text-sm text-muted-foreground md:text-base">
                        System activity and change history
                    </p>
                </div>
                <Button @click="handleExportCSV" variant="outline">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                        class="size-5 mr-2"
                    >
                        <path
                            d="M10.75 2.75a.75.75 0 00-1.5 0v8.614L6.295 8.235a.75.75 0 10-1.09 1.03l4.25 4.5a.75.75 0 001.09 0l4.25-4.5a.75.75 0 00-1.09-1.03l-2.955 3.129V2.75z"
                        />
                        <path
                            d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z"
                        />
                    </svg>
                    Export CSV
                </Button>
            </div>

            <!-- Search and Filters -->
            <div class="flex flex-col gap-3 md:flex-row">
                <Input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search by user email or action..."
                    @keydown.enter="handleSearch"
                    class="flex-1"
                />
                <Input
                    v-model="startDate"
                    type="date"
                    placeholder="Start date"
                    class="w-full md:w-48"
                />
                <Input
                    v-model="endDate"
                    type="date"
                    placeholder="End date"
                    class="w-full md:w-48"
                />
                <Button @click="handleSearch">Filter</Button>
            </div>

            <!-- Audit Logs Table (Desktop) -->
            <Card v-if="logs.data.length > 0" class="hidden md:block">
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="border-b bg-muted/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Action</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Resource</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">IP Address</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Details</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <template v-for="log in logs.data" :key="log.id">
                                    <tr class="hover:bg-muted/50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">
                                            {{ formatDate(log.created_at) }}
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <div v-if="log.user">
                                                <p class="font-medium">{{ log.user.name }}</p>
                                                <p class="text-xs text-muted-foreground">{{ log.user.email }}</p>
                                            </div>
                                            <span v-else class="text-muted-foreground">System</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <Badge :variant="getActionColor(log.action)">{{ log.action }}</Badge>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            {{ log.resource_type }}
                                            <span v-if="log.resource_id" class="text-muted-foreground">#{{ log.resource_id }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">
                                            {{ log.ip_address || '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right">
                                            <Button
                                                v-if="log.old_values || log.new_values"
                                                @click="toggleExpanded(log.id)"
                                                variant="ghost"
                                                size="sm"
                                            >
                                                {{ expandedLogId === log.id ? 'Hide' : 'Show' }}
                                            </Button>
                                        </td>
                                    </tr>
                                    <tr v-if="expandedLogId === log.id">
                                        <td colspan="6" class="px-6 py-4 bg-muted/30">
                                            <div class="grid gap-4 md:grid-cols-2">
                                                <div v-if="log.old_values" class="space-y-2">
                                                    <p class="text-sm font-medium">Old Values</p>
                                                    <pre class="text-xs bg-background p-3 rounded border overflow-x-auto">{{ JSON.stringify(log.old_values, null, 2) }}</pre>
                                                </div>
                                                <div v-if="log.new_values" class="space-y-2">
                                                    <p class="text-sm font-medium">New Values</p>
                                                    <pre class="text-xs bg-background p-3 rounded border overflow-x-auto">{{ JSON.stringify(log.new_values, null, 2) }}</pre>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <!-- Audit Logs List (Mobile) -->
            <div v-if="logs.data.length > 0" class="space-y-4 md:hidden">
                <Card v-for="log in logs.data" :key="log.id">
                    <CardContent class="pt-6 space-y-3">
                        <div class="flex items-start justify-between gap-4">
                            <Badge :variant="getActionColor(log.action)">{{ log.action }}</Badge>
                            <p class="text-xs text-muted-foreground">{{ formatDate(log.created_at) }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium">
                                {{ log.resource_type }}
                                <span v-if="log.resource_id" class="text-muted-foreground">#{{ log.resource_id }}</span>
                            </p>
                            <p v-if="log.user" class="text-xs text-muted-foreground">{{ log.user.email }}</p>
                        </div>
                        <Button
                            v-if="log.old_values || log.new_values"
                            @click="toggleExpanded(log.id)"
                            variant="outline"
                            size="sm"
                            class="w-full"
                        >
                            {{ expandedLogId === log.id ? 'Hide Details' : 'Show Details' }}
                        </Button>
                        <div v-if="expandedLogId === log.id" class="space-y-3 pt-3 border-t">
                            <div v-if="log.old_values" class="space-y-2">
                                <p class="text-sm font-medium">Old Values</p>
                                <pre class="text-xs bg-muted p-3 rounded overflow-x-auto">{{ JSON.stringify(log.old_values, null, 2) }}</pre>
                            </div>
                            <div v-if="log.new_values" class="space-y-2">
                                <p class="text-sm font-medium">New Values</p>
                                <pre class="text-xs bg-muted p-3 rounded overflow-x-auto">{{ JSON.stringify(log.new_values, null, 2) }}</pre>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <Card v-if="logs.data.length === 0">
                <CardContent class="flex flex-col items-center justify-center py-12 px-4 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-16 text-muted-foreground mb-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                    <h3 class="text-lg font-semibold mb-2">No logs found</h3>
                    <p class="text-sm text-muted-foreground max-w-md">
                        {{ search ? 'No logs match your search.' : 'No activity has been logged in this date range.' }}
                    </p>
                    <Button v-if="search || start_date || end_date" variant="outline" class="mt-4" @click="router.get('/admin/audit-logs')">
                        Clear filters
                    </Button>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div v-if="logs.data.length > 0" class="text-center text-sm text-muted-foreground pb-4">
                Page {{ logs.current_page }} of {{ logs.last_page }}
            </div>
        </div>
    </AppLayout>
</template>
