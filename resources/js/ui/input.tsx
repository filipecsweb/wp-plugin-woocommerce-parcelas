/**
 * shadcn/ui Input (base-nova), restyled as wp-admin's text field.
 *
 * @since 2.0.0
 */
import * as React from "react"
import { Input as InputPrimitive } from "@base-ui/react/input"
import { cn } from "@/ui/utils"

function Input({ className, type, ...props }: React.ComponentProps<"input">) {
  return (
    <InputPrimitive
      type={type}
      data-slot="input"
      className={cn(
        "tw:mx-px tw:block tw:h-(--control-height) tw:w-full tw:min-w-0 tw:rounded-control tw:border tw:border-solid tw:border-input tw:bg-background tw:px-3 tw:text-control tw:text-input-foreground tw:shadow-none tw:placeholder:text-placeholder tw:mobile:appearance-none tw:mobile:text-control-mobile",
        "tw:focus:border-primary tw:focus:shadow-focus tw:focus:outline-2 tw:focus:outline-solid tw:focus:outline-transparent",
        "tw:disabled:cursor-default tw:disabled:border-input-disabled-border tw:disabled:bg-input-disabled tw:disabled:text-input-disabled-foreground",
        className
      )}
      {...props}
    />
  )
}

export { Input }
