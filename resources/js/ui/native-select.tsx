/**
 * shadcn/ui NativeSelect (base-nova), restyled as wp-admin's `<select>`: the chevron
 * is the field's own background image, so there is no wrapper or icon element.
 *
 * @since 2.0.0
 */
import * as React from "react"
import { cn } from "@/ui/utils"

function NativeSelect({ className, ...props }: React.ComponentProps<"select">) {
  return (
    <select
      data-slot="native-select"
      className={cn(
        "tw:mx-px tw:block tw:min-h-(--control-height) tw:max-w-(--select-max-width) tw:cursor-pointer tw:appearance-none tw:rounded-control tw:border tw:border-solid tw:border-input tw:bg-background tw:bg-(image:--select-chevron) tw:bg-size-(--select-chevron-size) tw:bg-position-(--select-chevron-position) tw:rtl:bg-position-(--select-chevron-position-rtl) tw:bg-no-repeat tw:ps-3 tw:pe-6 tw:align-middle tw:text-select tw:text-input-foreground tw:shadow-none tw:mobile:text-select-mobile",
        "tw:hover:border-input-hover tw:focus:border-primary tw:focus:shadow-focus tw:focus:outline-2 tw:focus:outline-solid tw:focus:outline-transparent",
        "tw:disabled:cursor-default tw:disabled:opacity-(--select-disabled-opacity) tw:disabled:text-shadow-(--select-disabled-text-shadow) tw:disabled:border-select-disabled-border tw:disabled:bg-select-disabled tw:disabled:bg-(image:--select-chevron-disabled) tw:disabled:text-select-disabled-foreground",
        className
      )}
      {...props}
    />
  )
}

function NativeSelectOption(props: React.ComponentProps<"option">) {
  return <option data-slot="native-select-option" {...props} />
}

export { NativeSelect, NativeSelectOption }
