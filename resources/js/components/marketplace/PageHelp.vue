<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';

interface Props {
    /** Unique key used to persist dismissed state across navigation */
    storageKey: string;
    title?: string;
    steps: string[];
}

const props = withDefaults(defineProps<Props>(), {
    title: 'How this works',
});

const open = ref(false);

onMounted(() => {
    const dismissed = localStorage.getItem(`help-dismissed:${props.storageKey}`);
    open.value = dismissed !== '1';
});

const dismiss = () => {
    open.value = false;
    localStorage.setItem(`help-dismissed:${props.storageKey}`, '1');
};

const toggle = () => {
    open.value = !open.value;
    if (!open.value) {
        localStorage.setItem(`help-dismissed:${props.storageKey}`, '1');
    } else {
        localStorage.removeItem(`help-dismissed:${props.storageKey}`);
    }
};
</script>

<template>
    <Collapsible :open="open" class="w-full">
        <div class="flex items-center gap-2">
            <CollapsibleTrigger as-child>
                <button
                    type="button"
                    @click="toggle"
                    class="flex items-center gap-1.5 text-xs font-medium text-muted-foreground hover:text-foreground transition-colors"
                >
                    <!-- Info icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4 shrink-0 text-primary/70">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                    </svg>
                    {{ title }}
                    <!-- Chevron -->
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                        class="size-3.5 transition-transform duration-200"
                        :class="open ? 'rotate-180' : ''"
                    >
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </button>
            </CollapsibleTrigger>
        </div>

        <CollapsibleContent>
            <div class="mt-2 rounded-lg border border-primary/20 bg-primary/5 px-4 py-3">
                <ol class="space-y-1.5">
                    <li
                        v-for="(step, i) in steps"
                        :key="i"
                        class="flex items-start gap-2.5 text-sm text-muted-foreground"
                    >
                        <span class="mt-px flex size-4.5 shrink-0 items-center justify-center rounded-full bg-primary/15 text-[10px] font-bold text-primary">
                            {{ i + 1 }}
                        </span>
                        <span>{{ step }}</span>
                    </li>
                </ol>
                <button
                    type="button"
                    @click="dismiss"
                    class="mt-3 text-xs text-muted-foreground hover:text-foreground transition-colors"
                >
                    Got it — don't show again
                </button>
            </div>
        </CollapsibleContent>
    </Collapsible>
</template>
