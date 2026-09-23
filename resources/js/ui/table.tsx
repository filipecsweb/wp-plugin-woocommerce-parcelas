/**
 * shadcn/ui Table (base-nova), restyled as wp-admin's striped `.widefat` list table.
 * The parts compose around the caller's own `<table>` (`tableClass`), because a sticky
 * header pins to its nearest scrolling ancestor and shadcn's wrapper would be that.
 *
 * @since 2.0.0
 */
import * as React from "react"
import { cn } from "@/ui/utils"

// GOTCHA: separate borders with no spacing, as .widefat draws them; preflight's collapse would merge them.
const tableClass = "tw:w-full tw:**:wrap-break-word tw:border-separate tw:border-spacing-0 tw:border tw:border-solid tw:border-border tw:bg-card tw:text-body"

function TableHeader({ className, ...props }: React.ComponentProps<"thead">) {
  return <thead data-slot="table-header" className={className} {...props} />
}

function TableBody({ className, ...props }: React.ComponentProps<"tbody">) {
  return <tbody data-slot="table-body" className={className} {...props} />
}

function TableRow({ className, ...props }: React.ComponentProps<"tr">) {
  return <tr data-slot="table-row" className={cn("tw:even:bg-table-stripe", className)} {...props} />
}

function TableHead({ className, ...props }: React.ComponentProps<"th">) {
  return (
    <th
      data-slot="table-head"
      className={cn(
        "tw:border-b tw:border-solid tw:border-border tw:px-2.5 tw:py-2 tw:text-start tw:align-middle tw:text-table-head tw:font-normal tw:text-table-head-foreground",
        className
      )}
      {...props}
    />
  )
}

function TableCell({ className, ...props }: React.ComponentProps<"td">) {
  return (
    <td
      data-slot="table-cell"
      className={cn("tw:px-2.5 tw:py-2 tw:align-top tw:text-cell tw:text-table-cell-foreground", className)}
      {...props}
    />
  )
}

export { tableClass, TableHeader, TableBody, TableRow, TableHead, TableCell }
