/**
 * Busy indicator for buttons: a CSS ring in the current text colour.
 *
 * @since 2.0.0
 */
import { cn } from '@/ui/utils'

export function Spinner({ className }: { className?: string }) {
  return (
    <span
      aria-hidden="true"
      className={cn('tw:inline-block tw:size-3.5 tw:animate-spin tw:rounded-full tw:border-2 tw:border-solid tw:border-current tw:border-t-transparent', className)}
    />
  )
}
