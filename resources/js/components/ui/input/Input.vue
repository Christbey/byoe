<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { useVModel } from "@vueuse/core"
import { cn } from "@/lib/utils"

const props = defineProps<{
  defaultValue?: string | number
  modelValue?: string | number
  class?: HTMLAttributes["class"]
}>()

const emits = defineEmits<{
  (e: "update:modelValue", payload: string | number): void
}>()

const modelValue = useVModel(props, "modelValue", emits, {
  passive: true,
  defaultValue: props.defaultValue,
})
</script>

<template>
  <input
    v-model="modelValue"
    data-slot="input"
    :class="cn(
      'file:text-foreground placeholder:text-muted-foreground/90 selection:bg-primary selection:text-primary-foreground border-input h-12 w-full min-w-0 rounded-[20px] border bg-white/72 px-4 py-2 text-base shadow-[inset_0_1px_0_rgba(255,255,255,0.7)] backdrop-blur-md transition-[color,box-shadow,background-color,border-color] outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 dark:bg-white/8 md:text-sm',
      'focus-visible:border-ring focus-visible:bg-white/90 focus-visible:ring-ring/35 focus-visible:ring-[4px] dark:focus-visible:bg-white/12',
      'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
      props.class,
    )"
  >
</template>
