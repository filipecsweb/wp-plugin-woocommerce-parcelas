/**
 * shadcn/ui Badge (base-nova), restyled as a tinted status pill.
 *
 * @since 2.0.0
 */
import { mergeProps } from "@base-ui/react/merge-props"
import { useRender } from "@base-ui/react/use-render"
import { cva, type VariantProps } from "class-variance-authority"
import { cn } from "@/ui/utils"

const badgeVariants = cva("tw:inline-flex tw:items-center tw:rounded-badge tw:px-2 tw:py-0.5 tw:text-body tw:font-medium", {
  variants: {
    variant: {
      success: "tw:bg-badge-success tw:text-badge-success-foreground",
      destructive: "tw:bg-badge-destructive tw:text-badge-destructive-foreground",
    },
  },
  defaultVariants: {
    variant: "success",
  },
})

function Badge({
  className,
  variant = "success",
  render,
  ...props
}: useRender.ComponentProps<"span"> & VariantProps<typeof badgeVariants>) {
  return useRender({
    defaultTagName: "span",
    props: mergeProps<"span">(
      {
        className: cn(badgeVariants({ variant }), className),
      },
      props
    ),
    render,
    state: {
      slot: "badge",
      variant,
    },
  })
}

export { Badge, badgeVariants }
