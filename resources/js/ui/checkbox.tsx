/**
 * shadcn/ui Checkbox (base-nova), restyled as wp-admin's checkbox, tick included.
 *
 * @since 2.0.0
 */
import { Checkbox as CheckboxPrimitive } from "@base-ui/react/checkbox"
import { cn } from "@/ui/utils"

function Checkbox({ className, ...props }: CheckboxPrimitive.Root.Props) {
  return (
    <CheckboxPrimitive.Root
      data-slot="checkbox"
      className={cn(
        "tw:me-1 tw:block tw:size-4 tw:cursor-pointer tw:rounded-control tw:border tw:border-solid tw:border-(--checkbox-border) tw:bg-background tw:align-middle tw:transition-[border-color] tw:duration-50 tw:ease-core tw:mobile:size-(--checkbox-size-mobile)",
        "tw:data-checked:border-primary tw:data-checked:bg-primary",
        "tw:focus:border-(--checkbox-border) tw:data-checked:focus:border-(--checkbox-border) tw:focus:shadow-focus-checkbox tw:focus:outline-2 tw:focus:outline-solid tw:focus:outline-transparent",
        className
      )}
      {...props}
    >
      <CheckboxPrimitive.Indicator data-slot="checkbox-indicator" className="tw:-mt-0.75 tw:-ms-1 tw:block tw:size-(--checkbox-tick-size) tw:mobile:-mx-1.25 tw:mobile:-my-0.75 tw:mobile:size-(--checkbox-tick-size-mobile)">
        {/* The path of core's own checkbox tick, drawn at its size and offset. */}
        <svg viewBox="0 0 20 20" aria-hidden="true" className="tw:block tw:size-full">
          <path d="M14.83 4.89l1.34.94-5.81 8.38H9.02L5.78 9.67l1.34-1.25 2.57 2.4z" fill="var(--primary-foreground)" />
        </svg>
      </CheckboxPrimitive.Indicator>
    </CheckboxPrimitive.Root>
  )
}

export { Checkbox }
