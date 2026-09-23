/**
 * shadcn/ui Alert (base-nova), restyled as wp-admin's inline notice: a tinted box with
 * a status rule on its inline-start edge. The text is one paragraph (`AlertDescription`)
 * with an optional run-in `AlertTitle`, as core lays a notice out.
 *
 * @since 2.0.0
 */
import * as React from "react"
import { cva, type VariantProps } from "class-variance-authority"
import { cn } from "@/ui/utils"

const alertVariants = cva("tw:border-s-4 tw:border-solid tw:px-3 tw:py-2 tw:text-body tw:mobile:px-2.5 tw:mobile:py-1.25 tw:mobile:text-notice-mobile", {
  variants: {
    variant: {
      destructive: "tw:border-notice-error-border tw:bg-notice-error",
      warning: "tw:border-notice-warning-border tw:bg-notice-warning",
      success: "tw:border-notice-success-border tw:bg-notice-success",
    },
  },
  defaultVariants: {
    variant: "warning",
  },
})

function Alert({ className, variant, ...props }: React.ComponentProps<"div"> & VariantProps<typeof alertVariants>) {
  return <div data-slot="alert" role="alert" className={cn(alertVariants({ variant }), className)} {...props} />
}

function AlertTitle({ className, ...props }: React.ComponentProps<"strong">) {
  return <strong data-slot="alert-title" className={className} {...props} />
}

const alertDescriptionClass = "tw:my-(--notice-paragraph-margin) tw:text-notice tw:text-notice-foreground"

function AlertDescription({ className, ...props }: React.ComponentProps<"p">) {
  return <p data-slot="alert-description" className={cn(alertDescriptionClass, className)} {...props} />
}

export { Alert, AlertTitle, AlertDescription, alertVariants, alertDescriptionClass }
