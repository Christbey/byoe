<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    Briefcase,
    Building2,
    CreditCard,
    FileText,
    LayoutGrid,
    Settings,
    ShieldAlert,
    TrendingUp,
    User,
    Users,
    UserCheck,
} from 'lucide-vue-next';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import AppLogo from './AppLogo.vue';
import { dashboard } from '@/routes';

const page = usePage();
const user = computed(() => page.props.auth.user);

const hasRole = (role: string) => {
    return user.value?.roles?.includes(role) || false;
};

const isAdmin = computed(() => hasRole('admin'));
const isShopOwner = computed(() => hasRole('shop_owner') || hasRole('shop_manager'));
const isProvider = computed(() => hasRole('provider'));

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];

    // Default dashboard
    items.push({
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    });

    // Shop Portal - show to shop owners/managers and admins
    if (isShopOwner.value || isAdmin.value) {
        items.push(
            {
                title: 'Shop Profile',
                href: '/settings/shop',
                icon: Building2,
            },
            {
                title: 'Service Requests',
                href: '/shop/service-requests',
                icon: Briefcase,
            },
            {
                title: 'Payments',
                href: '/shop/payments',
                icon: CreditCard,
            }
        );
    }

    // Provider Portal - show to providers and admins
    if (isProvider.value || isAdmin.value) {
        items.push(
            {
                title: 'Provider Profile',
                href: '/settings/provider',
                icon: User,
            },
            {
                title: 'Available Requests',
                href: '/provider/available-requests',
                icon: Briefcase,
            },
            {
                title: 'Provider Bookings',
                href: '/provider/bookings',
                icon: FileText,
            },
            {
                title: 'Earnings & Payouts',
                href: '/provider/earnings',
                icon: TrendingUp,
            },
        );
    }

    // Admin Portal - only show to admins
    if (isAdmin.value) {
        items.push(
            {
                title: 'Admin Dashboard',
                href: '/admin/dashboard',
                icon: ShieldAlert,
            },
            {
                title: 'Provider Trust',
                href: '/admin/providers',
                icon: UserCheck,
            },
            {
                title: 'Users',
                href: '/admin/users',
                icon: Users,
            },
            {
                title: 'Disputes',
                href: '/admin/disputes',
                icon: ShieldAlert,
            },
            {
                title: 'Audit Logs',
                href: '/admin/audit-logs',
                icon: FileText,
            },
            {
                title: 'Settings',
                href: '/admin/settings',
                icon: Settings,
            }
        );
    }

    return items;
});

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader class="pt-safe px-3 pt-4">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        size="lg"
                        as-child
                        class="ios-panel h-14 rounded-[22px] border-white/50 px-3 dark:border-white/8"
                    >
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="px-3 pb-2">
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter class="px-3 pb-safe pb-4">
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
