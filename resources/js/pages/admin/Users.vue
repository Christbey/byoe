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

interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    status: 'active' | 'suspended' | 'inactive';
    created_at: string;
}

interface Props {
    users: PaginatedResponse<User>;
    search?: string;
    role_filter?: string;
}

const props = withDefaults(defineProps<Props>(), {
    search: '',
    role_filter: 'all',
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: '/admin/users',
    },
];

const searchQuery = ref(props.search);
const roleFilter = ref(props.role_filter);

const roles = [
    { key: 'all', label: 'All Roles' },
    { key: 'admin', label: 'Admin' },
    { key: 'shop', label: 'Shop' },
    { key: 'provider', label: 'Provider' },
    { key: 'user', label: 'User' },
];

const handleSearch = () => {
    router.get(
        '/admin/users',
        { search: searchQuery.value, role: roleFilter.value },
        { preserveState: true, preserveScroll: true },
    );
};

const handleRoleFilterChange = (role: string) => {
    roleFilter.value = role;
    router.get(
        '/admin/users',
        { search: searchQuery.value, role },
        { preserveState: true, preserveScroll: true },
    );
};

const handleSuspendUser = (userId: number) => {
    if (confirm('Suspend this user?')) {
        router.post(`/admin/users/${userId}/suspend`, {}, {
            preserveScroll: true,
        });
    }
};

const handleDeleteUser = (userId: number) => {
    if (confirm('Delete this user? This action cannot be undone.')) {
        router.delete(`/admin/users/${userId}`, {
            preserveScroll: true,
        });
    }
};

const formatDate = (date: string) => {
    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(new Date(date));
};

const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
        active: 'default',
        suspended: 'destructive',
        inactive: 'secondary',
    };
    return colors[status] || 'outline';
};
</script>

<template>
    <Head title="Users Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
            <!-- Header -->
            <div class="space-y-2">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                    Users Management
                </h1>
                <p class="text-sm text-muted-foreground md:text-base">
                    Manage platform users and roles
                </p>
            </div>

            <!-- Search and Filters -->
            <div class="flex flex-col gap-3 md:flex-row">
                <div class="flex gap-2 flex-1">
                    <Input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search by name or email..."
                        @keydown.enter="handleSearch"
                        class="flex-1"
                    />
                    <Button @click="handleSearch">Search</Button>
                </div>
                <select
                    v-model="roleFilter"
                    @change="handleRoleFilterChange(roleFilter)"
                    class="w-full md:w-48 min-w-0 rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] md:text-sm"
                >
                    <option v-for="role in roles" :key="role.key" :value="role.key">
                        {{ role.label }}
                    </option>
                </select>
            </div>

            <!-- Users Table (Desktop) -->
            <Card v-if="users.data.length > 0" class="hidden md:block">
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="border-b bg-muted/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Role</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Created</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr
                                    v-for="user in users.data"
                                    :key="user.id"
                                    class="hover:bg-muted/50 transition-colors"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">{{ user.name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ user.email }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <Badge variant="outline">{{ user.role }}</Badge>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <Badge :variant="getStatusColor(user.status)">{{ user.status }}</Badge>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ formatDate(user.created_at) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        <div class="flex items-center justify-end gap-2">
                                            <Button v-if="user.role !== 'admin'" as="a" :href="`/impersonate/user/${user.id}`" variant="ghost" size="sm" class="text-amber-600 hover:text-amber-700">
                                                Impersonate
                                            </Button>
                                            <Button as="a" :href="`/admin/users/${user.id}/edit`" variant="ghost" size="sm">Edit</Button>
                                            <Button v-if="user.status === 'active'" @click="handleSuspendUser(user.id)" variant="ghost" size="sm">Suspend</Button>
                                            <Button @click="handleDeleteUser(user.id)" variant="ghost" size="sm" class="text-destructive">Delete</Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <!-- Users List (Mobile) -->
            <div v-if="users.data.length > 0" class="space-y-4 md:hidden">
                <Card v-for="user in users.data" :key="user.id">
                    <CardContent class="pt-6 space-y-3">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <p class="font-medium truncate">{{ user.name }}</p>
                                <p class="text-sm text-muted-foreground truncate">{{ user.email }}</p>
                            </div>
                            <Badge :variant="getStatusColor(user.status)">{{ user.status }}</Badge>
                        </div>
                        <div class="flex items-center gap-2">
                            <Badge variant="outline">{{ user.role }}</Badge>
                            <span class="text-xs text-muted-foreground">{{ formatDate(user.created_at) }}</span>
                        </div>
                        <div class="flex gap-2 pt-2 border-t">
                            <Button v-if="user.role !== 'admin'" as="a" :href="`/impersonate/user/${user.id}`" variant="outline" size="sm" class="flex-1 text-amber-600 border-amber-300 hover:bg-amber-50">
                                Impersonate
                            </Button>
                            <Button as="a" :href="`/admin/users/${user.id}/edit`" variant="outline" size="sm" class="flex-1">Edit</Button>
                            <Button v-if="user.status === 'active'" @click="handleSuspendUser(user.id)" variant="outline" size="sm" class="flex-1">Suspend</Button>
                            <Button @click="handleDeleteUser(user.id)" variant="ghost" size="sm">Delete</Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <Card v-if="users.data.length === 0">
                <CardContent class="flex flex-col items-center justify-center py-12 px-4 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-16 text-muted-foreground mb-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                    <h3 class="text-lg font-semibold mb-2">No users found</h3>
                    <p class="text-sm text-muted-foreground max-w-md">
                        {{ search ? 'No users match your search. Try a different name or email.' : 'No users match the selected filter.' }}
                    </p>
                    <Button v-if="search || role_filter !== 'all'" variant="outline" class="mt-4" @click="router.get('/admin/users')">
                        Clear filters
                    </Button>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div v-if="users.data.length > 0" class="text-center text-sm text-muted-foreground pb-4">
                Page {{ users.current_page }} of {{ users.last_page }}
            </div>
        </div>
    </AppLayout>
</template>
