/**
 * shadcn/ui Tooltip (base-nova), restyled as a dark label under its trigger. The
 * portal renders into the shared container so the scoped reset still applies. Any
 * scroll closes it, except in a short window after opening, so the scroll that
 * reveals a keyboard-focused trigger doesn't dismiss the tooltip it just opened.
 *
 * @since 2.0.0
 */
import * as React from "react"
import { Tooltip as TooltipPrimitive } from "@base-ui/react/tooltip"
import { cn } from "@/ui/utils"
import { usePortalContainer } from "@/ui/portal"

const REVEAL_GRACE_MS = 150
const VIEWPORT_MARGIN_PX = 8

function TooltipProvider({ delay = 0, ...props }: TooltipPrimitive.Provider.Props) {
  return <TooltipPrimitive.Provider data-slot="tooltip-provider" delay={delay} {...props} />
}

function Tooltip(props: Omit<TooltipPrimitive.Root.Props, "open" | "onOpenChange">) {
  const [open, setOpen] = React.useState(false)

  React.useEffect(() => {
    if (!open) return
    const openedAt = performance.now()
    const onScroll = () => {
      if (performance.now() - openedAt >= REVEAL_GRACE_MS) setOpen(false)
    }
    // Capture: scroll doesn't bubble, and a scrolling ancestor other than the page counts too.
    window.addEventListener("scroll", onScroll, { capture: true, passive: true })
    return () => window.removeEventListener("scroll", onScroll, { capture: true })
  }, [open])

  return <TooltipPrimitive.Root data-slot="tooltip" open={open} onOpenChange={setOpen} {...props} />
}

function TooltipTrigger({ closeOnClick = false, ...props }: TooltipPrimitive.Trigger.Props) {
  return <TooltipPrimitive.Trigger data-slot="tooltip-trigger" closeOnClick={closeOnClick} {...props} />
}

function TooltipContent({
  className,
  side = "bottom",
  sideOffset = 4,
  align = "center",
  children,
  ...props
}: TooltipPrimitive.Popup.Props & Pick<TooltipPrimitive.Positioner.Props, "align" | "side" | "sideOffset">) {
  const container = usePortalContainer()
  return (
    <TooltipPrimitive.Portal container={container ?? undefined}>
      <TooltipPrimitive.Positioner positionMethod="fixed" align={align} side={side} sideOffset={sideOffset} collisionPadding={VIEWPORT_MARGIN_PX} className="tw:z-20">
        {/* content-box: the width cap applies to the text, and the padding adds to it. */}
        <TooltipPrimitive.Popup
          data-slot="tooltip-content"
          className={cn(
            "tw:box-content tw:block tw:w-max tw:wrap-break-word tw:max-w-(--tooltip-max-width) tw:rounded-badge tw:bg-tooltip-bg tw:px-2 tw:py-1 tw:text-tooltip tw:text-tooltip-foreground tw:shadow-tooltip",
            "tw:transition-opacity tw:duration-150 tw:data-starting-style:opacity-0 tw:data-ending-style:opacity-0",
            className
          )}
          {...props}
        >
          {children}
        </TooltipPrimitive.Popup>
      </TooltipPrimitive.Positioner>
    </TooltipPrimitive.Portal>
  )
}

export { Tooltip, TooltipTrigger, TooltipContent, TooltipProvider }
