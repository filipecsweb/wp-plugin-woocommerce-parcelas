/**
 * Inline code chip, as wp-admin draws `<code>`: monospace on a faint grey ground.
 * Code reads left to right whatever the page direction.
 *
 * @since 2.0.0
 */
import * as React from 'react'
import { cn } from '@/ui/utils'

export function Code({ className, ...props }: React.ComponentProps<'code'>) {
  return (
    <code
      dir="ltr"
      className={cn('tw:mx-px tw:bg-code tw:px-1.25 tw:pt-0.75 tw:pb-0.5 tw:font-mono tw:text-body tw:mobile:wrap-anywhere tw:mobile:[word-break:break-word]', className)}
      {...props}
    />
  )
}
