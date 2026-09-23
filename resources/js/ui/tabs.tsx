/**
 * shadcn/ui Tabs (base-nova), restyled as wp-admin's `.nav-tab-wrapper`: raised tabs
 * on a rule, the active one opening onto the page below it.
 *
 * @since 2.0.0
 */
import { Tabs as TabsPrimitive } from "@base-ui/react/tabs"
import { cn } from "@/ui/utils"

function Tabs({ className, orientation = "horizontal", ...props }: TabsPrimitive.Root.Props) {
  return (
    <TabsPrimitive.Root
      data-slot="tabs"
      data-orientation={orientation}
      className={cn("tw:flex tw:flex-col", className)}
      {...props}
    />
  )
}

function TabsList({ className, ...props }: TabsPrimitive.List.Props) {
  return (
    <TabsPrimitive.List
      data-slot="tabs-list"
      className={cn("tw:flex tw:items-start tw:border-b tw:border-solid tw:border-border tw:pt-2.25 tw:narrow:border-b-0", className)}
      {...props}
    />
  )
}

// GOTCHA: the active tab's -1px bottom margin and page-coloured bottom border are what
// open it onto the page; the focused active tab takes core's one-step-lighter ground, and
// a pressed tab drops its focus ring, as core's do.
function TabsTrigger({ className, ...props }: TabsPrimitive.Tab.Props) {
  return (
    <TabsPrimitive.Tab
      data-slot="tabs-trigger"
      className={cn(
        "tw:ms-(--tab-gap) tw:cursor-pointer tw:appearance-none tw:text-start tw:border tw:border-b-0 tw:border-solid tw:border-border tw:bg-tab-bg tw:px-2.5 tw:py-1.25 tw:text-tab tw:font-semibold tw:whitespace-nowrap tw:text-tab-foreground tw:no-underline tw:transition-[border,background,color] tw:duration-50 tw:ease-core",
        "tw:hover:bg-tab-hover-bg tw:hover:text-tab-hover-foreground tw:focus:rounded-control tw:focus:bg-tab-hover-bg tw:focus:text-tab-hover-foreground tw:focus:shadow-focus tw:focus:outline-2 tw:focus:outline-solid tw:focus:outline-transparent tw:focus:active:shadow-none",
        "tw:data-active:-mb-px tw:data-active:border-b tw:data-active:border-b-tab-active-bg tw:data-active:bg-tab-active-bg tw:data-active:text-tab-active-foreground",
        "tw:data-active:focus:border-b-tab-active-focus-bg tw:data-active:focus:bg-tab-active-focus-bg",
        "tw:narrow:ms-0 tw:narrow:me-2.5 tw:narrow:mt-2.5 tw:narrow:border-b tw:narrow:border-b-border tw:narrow:data-active:mb-0 tw:narrow:data-active:border-b-border",
        className
      )}
      {...props}
    />
  )
}

function TabsContent({ className, ...props }: TabsPrimitive.Panel.Props) {
  return (
    <TabsPrimitive.Panel
      data-slot="tabs-content"
      className={cn("tw:outline-none tw:transition-opacity tw:data-starting-style:opacity-0", className)}
      {...props}
    />
  )
}

export { Tabs, TabsList, TabsTrigger, TabsContent }
