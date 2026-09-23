/**
 * Style tab: color, weight and size for each part of each line, per context. An
 * empty field keeps the theme's own style.
 *
 * @since 2.0.0
 */
import { __ } from '@wordpress/i18n'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/ui/card'
import { Input } from '@/ui/input'
import { ChoiceSelect, ColorField } from './fields'
import type { Actions, Config, Context, Kind, Part, Settings } from './store'

interface Props {
  cfg: Config
  draft: Settings
  actions: Actions
}

export default function StyleTab({ cfg, draft, actions }: Props) {
  return (
    <>
      <StyleCard kind="installments" title={__('Installment price', 'woocommerce-parcelas')} cfg={cfg} draft={draft} actions={actions} />
      <StyleCard kind="cash" title={__('Cash price', 'woocommerce-parcelas')} cfg={cfg} draft={draft} actions={actions} />
    </>
  )
}

function StyleCard({ kind, title, cfg, draft, actions }: Props & { kind: Kind; title: string }) {
  const contexts: Record<Context, string> = {
    loop: __('Product lists', 'woocommerce-parcelas'),
    single: __('Product page', 'woocommerce-parcelas'),
  }
  const parts: Record<Part, string> = {
    prefix: __('Text before', 'woocommerce-parcelas'),
    amount: __('Amount', 'woocommerce-parcelas'),
    suffix: __('Text after', 'woocommerce-parcelas'),
  }
  const columns = [__('Color', 'woocommerce-parcelas'), __('Weight', 'woocommerce-parcelas'), __('Size', 'woocommerce-parcelas')]

  return (
    <Card>
      <CardHeader>
        <CardTitle render={<h2 />}>{title}</CardTitle>
      </CardHeader>
      <CardContent className="tw:flex tw:flex-col tw:gap-5">
        <CardDescription>{__('Leave a field empty to keep your theme’s style.', 'woocommerce-parcelas')}</CardDescription>
        {(Object.keys(contexts) as Context[]).map((context) => (
          <fieldset key={context} className="tw:flex tw:flex-col tw:gap-2">
            <legend className="tw:mb-2 tw:text-label tw:font-semibold">{contexts[context]}</legend>
            {/* Visual column heads; each control carries its full name for assistive tech. */}
            <div aria-hidden="true" className="tw:grid tw:grid-cols-[112px_1fr_1fr_1fr] tw:gap-3 tw:text-body tw:text-muted-foreground tw:narrow:hidden">
              <span />
              {columns.map((column) => (
                <span key={column}>{column}</span>
              ))}
            </div>
            {(Object.keys(parts) as Part[]).map((part) => {
              const style = draft.style[kind][context][part]
              const name = (column: string) => `${contexts[context]}, ${parts[part]}: ${column}`
              const set = (patch: Partial<typeof style>) => actions.style(kind, context, part, patch)
              return (
                <div key={part} className="tw:grid tw:grid-cols-[112px_1fr_1fr_1fr] tw:items-center tw:gap-3 tw:narrow:grid-cols-1 tw:narrow:gap-1.5">
                  <span className="tw:text-body">{parts[part]}</span>
                  <ColorField label={name(columns[0])} value={style.color} onChange={(color) => set({ color })} />
                  <ChoiceSelect aria-label={name(columns[1])} choices={cfg.choices.weights} value={style.weight} onChange={(weight) => set({ weight })} />
                  <Input aria-label={name(columns[2])} placeholder={__('e.g. 18px', 'woocommerce-parcelas')} spellCheck={false} value={style.size} onChange={(event) => set({ size: event.target.value.trim() })} />
                </div>
              )
            })}
          </fieldset>
        ))}
      </CardContent>
    </Card>
  )
}
