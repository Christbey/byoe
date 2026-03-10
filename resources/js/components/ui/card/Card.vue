<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <div
    data-slot="card"
    :class="
      cn(
        'bg-card text-card-foreground flex flex-col gap-6 rounded-[28px] border border-white/45 py-6 shadow-[0_18px_48px_-28px_rgba(15,23,42,0.35)] backdrop-blur-xl dark:border-white/8 dark:bg-white/6',
        props.class,
      )
    "
  >
    <slot />
  </div>
</template>
