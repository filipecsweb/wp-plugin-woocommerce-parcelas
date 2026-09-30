/**
 * The screen's form rows, composed from the kit: a labelled control with an optional
 * hint, a checkbox row, and a color field.
 *
 * @since 2.0.0
 */
import { useId, type ReactNode } from 'react'
import { __ } from '@wordpress/i18n'
import { XIcon } from 'lucide-react'
import { Button } from '@/ui/button'
import { Checkbox } from '@/ui/checkbox'
import { Input } from '@/ui/input'
import { NativeSelect, NativeSelectOption } from '@/ui/native-select'
import { cn } from '@/ui/utils'
import type { Choice } from './store'

export interface ControlProps {
  id: string
  'aria-describedby'?: string
}

/** The hint is the control's description, not part of its name. */
export function Field({ label, hint, className, children }: { label: string; hint?: ReactNode; className?: string; children: (props: ControlProps) => ReactNode }) {
  const id = useId()
  const hintId = hint ? `${id}-hint` : undefined
  return (
    <div className={cn('tw:flex tw:flex-col tw:gap-1', className)}>
      <label htmlFor={id} className="tw:text-label tw:font-semibold">
        {label}
      </label>
      {children({ id, 'aria-describedby': hintId })}
      {hint && (
        <p id={hintId} className="tw:text-body tw:text-muted-foreground">
          {hint}
        </p>
      )}
    </div>
  )
}

export function CheckboxField({ label, hint, checked, onChange }: { label: string; hint?: string; checked: boolean; onChange: (checked: boolean) => void }) {
  return (
    <label className="tw:flex tw:cursor-pointer tw:items-start tw:gap-3">
      <Checkbox className="tw:mt-0.5" checked={checked} onCheckedChange={onChange} />
      <span className="tw:flex tw:flex-col">
        <span className="tw:text-label tw:font-semibold">{label}</span>
        {hint && <span className="tw:text-body tw:text-muted-foreground">{hint}</span>}
      </span>
    </label>
  )
}

export function ChoiceSelect({ choices, value, onChange, className, ...props }: { choices: Choice[]; value: string; onChange: (value: string) => void; className?: string } & Partial<ControlProps> & { 'aria-label'?: string }) {
  return (
    <NativeSelect className={cn('tw:w-full', className)} value={value} onChange={(event) => onChange(event.target.value)} {...props}>
      {choices.map((choice) => (
        <NativeSelectOption key={choice.value} value={choice.value}>
          {choice.label}
        </NativeSelectOption>
      ))}
    </NativeSelect>
  )
}

/**
 * A hex code field fronted by a swatch that opens the browser's color picker. Empty
 * means the theme's own color, which the swatch shows as a struck-through box.
 */
export function ColorField({ label, value, onChange }: { label: string; value: string; onChange: (value: string) => void }) {
  return (
    <div className="tw:flex tw:min-w-0 tw:items-center tw:gap-1.5">
      <span
        className="tw:relative tw:block tw:size-8 tw:shrink-0 tw:overflow-hidden tw:rounded-control tw:border tw:border-solid tw:border-input"
        style={value ? { backgroundColor: value } : { backgroundImage: 'linear-gradient(to top right, transparent calc(50% - 1px), var(--destructive) 50%, transparent calc(50% + 1px))' }}
      >
        <input
          type="color"
          aria-label={`${label} — ${__('picker', 'woocommerce-parcelas')}`}
          value={value || '#000000'}
          onChange={(event) => onChange(event.target.value)}
          className="tw:absolute tw:inset-0 tw:size-full tw:cursor-pointer tw:opacity-0"
        />
      </span>
      <Input
        aria-label={label}
        value={value}
        placeholder={__('Theme', 'woocommerce-parcelas')}
        spellCheck={false}
        onChange={(event) => onChange(event.target.value.trim())}
        className="tw:mx-0 tw:min-w-0 tw:font-mono"
      />
      {value && (
        <Button variant="ghost" size="icon" className="tw:size-8 tw:shrink-0" aria-label={`${label} — ${__('use the theme color', 'woocommerce-parcelas')}`} onClick={() => onChange('')}>
          <XIcon aria-hidden="true" className="tw:size-4" />
        </Button>
      )}
    </div>
  )
}
