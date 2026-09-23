/**
 * Toast host on Base UI's Toast, rendered into the shared portal container so the
 * scoped reset applies. The manager lives outside React, so notify() works from
 * anywhere without a hook. Each toast runs its own countdown, which both dismisses it
 * and drains the ring around its close button; Base UI's timer stays off, so hovering
 * doesn't pause it.
 *
 * @since 2.0.0
 */
import { useEffect, useState } from 'react'
import { Toast } from '@base-ui/react/toast'
import { __ } from '@wordpress/i18n'
import { XIcon } from 'lucide-react'
import { alertDescriptionClass, alertVariants } from '@/ui/alert'
import { buttonVariants } from '@/ui/button'
import { usePortalContainer } from '@/ui/portal'
import { cn } from '@/ui/utils'
import type { Notify } from './store'

const TIMEOUT_MS = 10_000

const manager = Toast.createToastManager()

// The id is the message: re-raising it restarts the countdown instead of stacking a duplicate.
export const notify: Notify = (type, text) => {
  manager.add({ id: `${type}:${text}`, type, title: text, timeout: 0, priority: type === 'error' ? 'high' : 'low' })
}

export function Toaster() {
  const container = usePortalContainer()
  return (
    <Toast.Provider toastManager={manager}>
      <Toast.Portal container={container ?? undefined}>
        <Toast.Viewport
          aria-label={__('Notifications', 'woocommerce-parcelas')}
          className="tw:fixed tw:end-4 tw:bottom-4 tw:z-[100001] tw:flex tw:w-80 tw:max-w-(--toast-max-width) tw:flex-col tw:gap-2"
        >
          <ToastList />
        </Toast.Viewport>
      </Toast.Portal>
    </Toast.Provider>
  )
}

function ToastList() {
  const { toasts } = Toast.useToastManager()
  // Oldest first, so a new toast lands at the bottom of the stack.
  return [...toasts].reverse().map((toast) => <ToastItem key={toast.id} toast={toast} />)
}

function ToastItem({ toast }: { toast: Toast.Root.ToastObject }) {
  const remaining = useCountdown(TIMEOUT_MS, toast.updateKey ?? 0)
  const error = toast.type === 'error'

  useEffect(() => {
    if (remaining === 0) manager.close(toast.id)
  }, [remaining, toast.id])

  return (
    <Toast.Root
      toast={toast}
      swipeDirection={[]}
      data-testid={`toast-${toast.type}`}
      className={cn(
        alertVariants({ variant: error ? 'destructive' : 'success' }),
        'tw:relative tw:pe-(--notice-dismissible-padding) tw:mobile:pe-(--notice-dismissible-padding-mobile) tw:transition tw:duration-200 tw:ease-out tw:data-ending-style:duration-150 tw:data-ending-style:ease-in',
        'tw:data-starting-style:translate-x-4 tw:data-starting-style:opacity-0 tw:data-ending-style:translate-x-4 tw:data-ending-style:opacity-0',
        'tw:rtl:data-starting-style:-translate-x-4 tw:rtl:data-ending-style:-translate-x-4'
      )}
    >
      <Toast.Title render={<p />} className={alertDescriptionClass} />
      <Toast.Close
        aria-label={__('Dismiss this notice.', 'woocommerce-parcelas')}
        className={cn(buttonVariants({ variant: 'ghost', size: 'icon' }), 'tw:group/dismiss tw:absolute tw:end-1 tw:top-1')}
      >
        <CountdownRing remaining={remaining} className={error ? 'tw:text-countdown-error' : 'tw:text-countdown-success'} />
        <XIcon aria-hidden="true" className="tw:size-5 tw:group-hover/dismiss:opacity-70 tw:group-active/dismiss:opacity-70" />
      </Toast.Close>
    </Toast.Root>
  )
}

/**
 * Share of `durationMs` left (1 → 0), counted on animation frames. A new `restartKey`
 * starts it over. Frames pause in a hidden tab, and so does the count.
 */
function useCountdown(durationMs: number, restartKey: number): number {
  const [remaining, setRemaining] = useState(1)

  useEffect(() => {
    let start: number | null = null
    let frame = 0
    const tick = (now: number) => {
      start ??= now
      const left = Math.max(0, 1 - (now - start) / durationMs)
      setRemaining(left)
      if (left > 0) frame = requestAnimationFrame(tick)
    }
    frame = requestAnimationFrame(tick)
    return () => cancelAnimationFrame(frame)
  }, [durationMs, restartKey])

  return remaining
}

function CountdownRing({ remaining, className }: { remaining: number; className: string }) {
  return (
    <svg viewBox="0 0 34 34" aria-hidden="true" className={cn('tw:pointer-events-none tw:absolute tw:inset-0 tw:size-full', className)}>
      <circle cx="17" cy="17" r="14" fill="none" stroke="currentColor" strokeWidth="2.5" opacity="0.2" />
      <circle
        cx="17"
        cy="17"
        r="14"
        fill="none"
        stroke="currentColor"
        strokeWidth="2.5"
        strokeLinecap="round"
        transform="rotate(-90 17 17)"
        pathLength={100}
        strokeDasharray="100"
        strokeDashoffset={100 - remaining * 100}
      />
    </svg>
  )
}
