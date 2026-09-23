/**
 * shadcn/ui Dialog (base-nova), restyled as a centred white panel over a dimmed page:
 * a header bar holding the title and a × close, then a padded body. The portal renders
 * into the shared container (so the scoped reset still applies), the layers sit at
 * core's modal level, and "Close" is translatable.
 *
 * @since 2.0.0
 */
import * as React from "react"
import { Dialog as DialogPrimitive } from "@base-ui/react/dialog"
import { __ } from "@wordpress/i18n"
import { cn } from "@/ui/utils"

import { Button } from "@/ui/button"
import { usePortalContainer } from "@/ui/portal"

// WHY: shadcn's z-50 sits under wp-admin's menu (9990) and toolbar (99999); 100000 is core's own modal level.
const MODAL_Z = "tw:z-[100000]"

// WHY trap-focus: focus stays in the dialog, while the page keeps its scrollbar and scroll, as core's modals do.
function Dialog({ modal = "trap-focus", ...props }: DialogPrimitive.Root.Props) {
  return <DialogPrimitive.Root data-slot="dialog" modal={modal} {...props} />
}

function DialogContent({ className, children, ...props }: DialogPrimitive.Popup.Props) {
  const container = usePortalContainer()
  return (
    <DialogPrimitive.Portal data-slot="dialog-portal" container={container ?? undefined}>
      {/* WHY the dim is on the viewport, not a Backdrop: one layer holds the dim and the panel's
          shadow, as core's overlay does, so the shadow blends into the dim the same way. */}
      <DialogPrimitive.Viewport
        data-slot="dialog-overlay"
        className={cn("tw:fixed tw:inset-0 tw:flex tw:items-center tw:justify-center tw:bg-overlay tw:p-4", MODAL_Z)}
      >
        <DialogPrimitive.Popup
          data-slot="dialog-content"
          className={cn("tw:w-full tw:max-w-(--dialog-max-width) tw:rounded-dialog tw:bg-background tw:shadow-dialog tw:outline-none", className)}
          {...props}
        >
          {children}
        </DialogPrimitive.Popup>
      </DialogPrimitive.Viewport>
    </DialogPrimitive.Portal>
  )
}

// GOTCHA: the close button comes first among the popup's controls, so opening the dialog focuses it.
function DialogHeader({ className, children, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="dialog-header"
      className={cn("tw:flex tw:items-center tw:justify-between tw:gap-4 tw:border-b tw:border-solid tw:border-border-muted tw:px-4 tw:py-3", className)}
      {...props}
    >
      {children}
      <DialogPrimitive.Close
        data-slot="dialog-close"
        aria-label={__("Close", "woocommerce-parcelas")}
        render={<Button variant="link" className="tw:text-muted-foreground tw:hover:text-muted-foreground tw:active:text-muted-foreground tw:focus:text-muted-foreground" />}
      >
        <span aria-hidden="true" className="tw:text-glyph">
          ×
        </span>
      </DialogPrimitive.Close>
    </div>
  )
}

function DialogBody({ className, ...props }: React.ComponentProps<"div">) {
  return <div data-slot="dialog-body" className={cn("tw:p-4", className)} {...props} />
}

function DialogFooter({ className, ...props }: React.ComponentProps<"div">) {
  return <div data-slot="dialog-footer" className={cn("tw:flex tw:items-center tw:justify-end tw:gap-2", className)} {...props} />
}

function DialogTitle({ className, ...props }: DialogPrimitive.Title.Props) {
  return <DialogPrimitive.Title data-slot="dialog-title" className={cn("tw:text-label tw:font-semibold tw:text-heading", className)} {...props} />
}

export { Dialog, DialogBody, DialogContent, DialogFooter, DialogHeader, DialogTitle }
