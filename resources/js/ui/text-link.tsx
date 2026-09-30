/**
 * Inline text link, as wp-admin draws `<a>`: underlined, darkening on hover and focus.
 *
 * @since 2.0.0
 */
import * as React from 'react'
import { cn } from '@/ui/utils'

export function TextLink({ className, ...props }: React.ComponentProps<'a'>) {
  return (
    <a
      className={cn(
        'tw:cursor-pointer tw:text-link tw:underline tw:transition-[border,background,color] tw:duration-50 tw:ease-core tw:hover:text-link-hover tw:active:text-link-hover',
        'tw:focus:rounded-control tw:focus:text-link-hover tw:focus:shadow-focus tw:focus:outline-2 tw:focus:outline-solid tw:focus:outline-transparent',
        className
      )}
      {...props}
    />
  )
}
