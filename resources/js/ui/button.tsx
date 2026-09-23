/**
 * shadcn/ui Button (base-nova), restyled as wp-admin's `.button` family: `default`
 * is `.button-primary`, `outline` is `.button`, `link` is `.button-link` and `ghost`
 * is a notice's dismiss button. It forwards its ref: core ships React 18, where a
 * plain function component given to a Base UI `render=` prop loses the ref it needs.
 *
 * @since 2.0.0
 */
import * as React from "react"
import { Button as ButtonPrimitive } from "@base-ui/react/button"
import { cva, type VariantProps } from "class-variance-authority"
import { cn } from "@/ui/utils"

// GOTCHA: focus rings use :focus, not :focus-visible, so a clicked button shows one too, as core's do.
const boxed =
  "tw:inline-block tw:rounded-control tw:border tw:border-solid tw:text-center tw:align-top tw:font-medium tw:whitespace-nowrap tw:no-underline tw:cursor-pointer tw:appearance-none tw:focus:shadow-focus tw:focus:outline-1 tw:focus:outline-solid tw:focus:outline-transparent tw:focus:outline-offset-0 tw:disabled:cursor-not-allowed tw:disabled:shadow-none tw:mobile:mb-1 tw:mobile:min-h-(--control-height) tw:mobile:px-(--button-padding-x-mobile) tw:mobile:align-middle tw:mobile:text-button-mobile"

const buttonVariants = cva("tw:[&_svg]:pointer-events-none tw:[&_svg]:shrink-0 tw:[&_svg:not([class*=size-])]:size-4", {
  variants: {
    variant: {
      default: cn(
        boxed,
        "tw:border-transparent tw:bg-primary tw:text-primary-foreground tw:hover:bg-primary-hover tw:focus:bg-primary tw:focus:shadow-focus-primary tw:active:bg-primary-active",
        "tw:disabled:border-disabled tw:disabled:bg-disabled tw:disabled:text-disabled-foreground"
      ),
      outline: cn(
        boxed,
        "tw:border-primary tw:bg-transparent tw:text-primary tw:hover:border-primary-active tw:hover:bg-primary-tint-hover tw:hover:text-primary-active tw:focus:border-primary tw:focus:bg-transparent tw:focus:text-primary tw:active:border-primary-active tw:active:bg-primary-tint-active tw:active:text-primary-active tw:active:shadow-none",
        "tw:disabled:border-disabled-border tw:disabled:bg-transparent tw:disabled:text-disabled-foreground"
      ),
      destructive: cn(
        boxed,
        "tw:border-destructive tw:bg-transparent tw:text-destructive tw:hover:bg-primary-tint-hover tw:focus:bg-transparent tw:active:bg-primary-tint-active tw:active:shadow-none",
        "tw:disabled:border-destructive-disabled-border tw:disabled:bg-transparent tw:disabled:text-destructive-disabled"
      ),
      link: "tw:cursor-pointer tw:appearance-auto tw:leading-[normal] tw:rounded-none tw:border-0 tw:bg-transparent tw:p-0 tw:text-start tw:text-link tw:underline tw:transition-[border,background,color] tw:duration-50 tw:ease-core tw:hover:text-link-hover tw:active:text-link-hover tw:focus:rounded-control tw:focus:text-link tw:focus:shadow-focus tw:focus:outline-1 tw:focus:outline-solid tw:focus:outline-transparent",
      ghost: "tw:flex tw:cursor-pointer tw:appearance-auto tw:leading-[normal] tw:items-center tw:justify-center tw:rounded-control tw:border-0 tw:bg-transparent tw:p-0 tw:text-notice-foreground tw:focus:shadow-focus tw:focus:outline-2 tw:focus:outline-solid tw:focus:outline-transparent",
    },
    size: {
      default: "",
      sm: "",
      icon: "tw:size-(--dismiss-size)",
    },
  },
  compoundVariants: [
    { variant: ["default", "outline", "destructive"], size: "default", className: "tw:min-h-(--control-height) tw:px-(--button-padding-x) tw:text-button" },
    { variant: ["default", "outline", "destructive"], size: "sm", className: "tw:min-h-(--control-height-sm) tw:px-(--button-padding-x-sm) tw:text-button-sm" },
  ],
  defaultVariants: {
    variant: "default",
    size: "default",
  },
})

type Variant = NonNullable<VariantProps<typeof buttonVariants>["variant"]>

// WHY the inner span: an adorned label (an icon or spinner beside the text) sits in an
// inline-flex box aligned to the middle of the 38px line, which makes such a button 40.53px
// tall; a plain-text one has no box and stays 40px, as core's do.
const BOXED = new Set<Variant>(["default", "outline", "destructive"])

const Button = React.forwardRef<
  HTMLElement,
  ButtonPrimitive.Props & VariantProps<typeof buttonVariants>
>(function Button({ className, variant = "default", size = "default", children, ...props }, ref) {
  return (
    <ButtonPrimitive
      data-slot="button"
      className={cn(buttonVariants({ variant, size, className }))}
      {...props}
      ref={ref}
    >
      {BOXED.has(variant ?? "default") && React.Children.count(children) > 1 ? <span className="tw:inline-flex tw:items-center tw:gap-2 tw:align-middle">{children}</span> : children}
    </ButtonPrimitive>
  )
})

export { Button, buttonVariants }
