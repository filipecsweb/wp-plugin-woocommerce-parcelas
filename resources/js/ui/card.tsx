/**
 * shadcn/ui Card (base-nova), restyled as wp-admin's `.postbox`: a flat bordered box
 * whose header bar carries the title and an optional action, above an inset body.
 * `CardTitle` takes a Base UI `render` element, so the screen picks the heading level.
 *
 * @since 2.0.0
 */
import * as React from "react"
import { mergeProps } from "@base-ui/react/merge-props"
import { useRender } from "@base-ui/react/use-render"
import { cn } from "@/ui/utils"

function Card({ className, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="card"
      className={cn(
        "tw:relative tw:min-w-(--card-min-width) tw:border tw:border-solid tw:border-border tw:bg-card tw:leading-none tw:shadow-card tw:mobile:text-body-mobile",
        className
      )}
      {...props}
    />
  )
}

function CardHeader({ className, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="card-header"
      className={cn("tw:flex tw:items-center tw:justify-between tw:border-b tw:border-solid tw:border-border", className)}
      {...props}
    />
  )
}

function CardTitle({ className, render, ...props }: useRender.ComponentProps<"div">) {
  return useRender({
    defaultTagName: "div",
    props: mergeProps<"div">(
      {
        className: cn(
          "tw:flex tw:grow tw:items-center tw:justify-between tw:px-4 tw:py-3 tw:text-label tw:font-semibold tw:text-heading",
          className
        ),
      },
      props
    ),
    render,
    state: { slot: "card-title" },
  })
}

function CardDescription({ className, ...props }: React.ComponentProps<"p">) {
  return (
    <p
      data-slot="card-description"
      className={cn("tw:text-paragraph tw:text-description tw:[&_code]:text-code-foreground", className)}
      {...props}
    />
  )
}

function CardAction({ className, ...props }: React.ComponentProps<"div">) {
  return <div data-slot="card-action" className={cn("tw:flex tw:shrink-0 tw:items-center tw:pe-2", className)} {...props} />
}

function CardContent({ className, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="card-content"
      className={cn("tw:relative tw:my-2.75 tw:px-3 tw:pb-3 tw:text-body tw:leading-(--line-height-card)", className)}
      {...props}
    />
  )
}

export { Card, CardHeader, CardTitle, CardAction, CardDescription, CardContent }
