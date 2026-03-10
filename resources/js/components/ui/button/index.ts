import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as Button } from "./Button.vue"

export const buttonVariants = cva(
  "inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-full text-sm font-medium transition-all duration-200 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/40 focus-visible:ring-[4px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive active:scale-[0.985]",
  {
    variants: {
      variant: {
        default:
          "bg-primary text-primary-foreground shadow-[0_10px_24px_-12px_hsl(var(--primary)/0.78)] hover:bg-primary/92",
        destructive:
          "bg-destructive text-white shadow-[0_10px_24px_-12px_rgba(239,68,68,0.65)] hover:bg-destructive/90 focus-visible:ring-destructive/20 dark:focus-visible:ring-destructive/40 dark:bg-destructive/60",
        outline:
          "border border-white/55 bg-white/72 text-foreground shadow-[0_10px_28px_-20px_rgba(15,23,42,0.35)] backdrop-blur-md hover:bg-white/88 hover:text-accent-foreground dark:border-white/10 dark:bg-white/8 dark:hover:bg-white/12",
        secondary:
          "bg-secondary text-secondary-foreground hover:bg-secondary/92",
        ghost:
          "text-muted-foreground hover:bg-accent/80 hover:text-accent-foreground dark:hover:bg-accent/70",
        link: "text-primary underline-offset-4 hover:underline",
        success:
          "bg-emerald-600 text-white hover:bg-emerald-600/90 focus-visible:ring-emerald-600/20 dark:focus-visible:ring-emerald-400/40 dark:bg-emerald-700 dark:hover:bg-emerald-700/90",
        warning:
          "bg-amber-500 text-white hover:bg-amber-500/90 focus-visible:ring-amber-500/20 dark:focus-visible:ring-amber-400/40 dark:bg-amber-600 dark:hover:bg-amber-600/90",
      },
      size: {
        "default": "h-11 px-5 py-2.5 has-[>svg]:px-4",
        "sm": "h-9 gap-1.5 px-3.5 has-[>svg]:px-3",
        "lg": "h-12 px-6 text-[0.95rem] has-[>svg]:px-5",
        "icon": "size-11",
        "icon-sm": "size-9",
        "icon-lg": "size-12",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  },
)
export type ButtonVariants = VariantProps<typeof buttonVariants>
