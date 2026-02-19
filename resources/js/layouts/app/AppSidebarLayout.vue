<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage<{
    impersonating: boolean;
    auth: { user: { name: string } | null };
    flash: { success: string | null; error: string | null; info: string | null };
}>();

const isImpersonating = computed(() => page.props.impersonating);
const impersonatedUser = computed(() => page.props.auth.user?.name ?? '');

const toast = ref<{ message: string; type: 'success' | 'error' | 'info' } | null>(null);
let toastTimer: ReturnType<typeof setTimeout>;

watch(
    () => page.props.flash,
    (flash) => {
        clearTimeout(toastTimer);
        if (flash?.success) {
            toast.value = { message: flash.success, type: 'success' };
        } else if (flash?.error) {
            toast.value = { message: flash.error, type: 'error' };
        } else if (flash?.info) {
            toast.value = { message: flash.info, type: 'info' };
        } else {
            return;
        }
        toastTimer = setTimeout(() => { toast.value = null; }, 5000);
    },
    { deep: true },
);
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <!-- Impersonation banner -->
            <div v-if="isImpersonating" class="flex items-center justify-between gap-4 bg-amber-500 px-4 py-2 text-sm font-medium text-amber-950">
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4 shrink-0">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-5.5-2.5a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0zM10 12a5.99 5.99 0 00-4.793 2.39A6.483 6.483 0 0010 16.5a6.483 6.483 0 004.793-2.11A5.99 5.99 0 0010 12z" clip-rule="evenodd" />
                    </svg>
                    <span>Impersonating <strong>{{ impersonatedUser }}</strong></span>
                </div>
                <a
                    href="/impersonate/leave"
                    class="rounded bg-amber-950/20 px-3 py-1 text-xs font-semibold hover:bg-amber-950/30 transition-colors"
                >
                    Stop Impersonating
                </a>
            </div>
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />

            <!-- Legal footer -->
            <footer class="mt-auto border-t px-4 py-3 md:px-6">
                <nav class="flex flex-wrap items-center gap-x-4 gap-y-1">
                    <span class="text-xs text-muted-foreground">
                        &copy; {{ new Date().getFullYear() }} BYOE
                    </span>
                    <a href="/legal/terms" class="text-xs text-muted-foreground transition-colors hover:text-foreground">Terms of Service</a>
                    <a href="/legal/privacy" class="text-xs text-muted-foreground transition-colors hover:text-foreground">Privacy Policy</a>
                    <a href="/legal/cookies" class="text-xs text-muted-foreground transition-colors hover:text-foreground">Cookie Policy</a>
                    <a href="/legal/contractor" class="text-xs text-muted-foreground transition-colors hover:text-foreground">Contractor Agreement</a>
                </nav>
            </footer>

            <!-- Flash toast -->
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 translate-y-2"
            >
                <div
                    v-if="toast"
                    class="fixed bottom-safe-4 right-safe-4 z-50 flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium shadow-lg"
                    :class="{
                        'bg-green-600 text-white': toast.type === 'success',
                        'bg-destructive text-destructive-foreground': toast.type === 'error',
                        'bg-blue-600 text-white': toast.type === 'info',
                    }"
                >
                    <svg v-if="toast.type === 'success'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4 shrink-0">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                    </svg>
                    <svg v-else-if="toast.type === 'info'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4 shrink-0">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                    </svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4 shrink-0">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                    </svg>
                    {{ toast.message }}
                </div>
            </Transition>
        </AppContent>
    </AppShell>
</template>
