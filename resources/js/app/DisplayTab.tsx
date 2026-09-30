/**
 * Display tab: where the lines go in product lists and on the product page.
 *
 * @since 2.0.0
 */
import { __ } from '@wordpress/i18n'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/ui/card'
import { Input } from '@/ui/input'
import { ChoiceSelect, Field } from './fields'
import type { Actions, Choice, Config, Context, Settings } from './store'

interface Props {
  cfg: Config
  draft: Settings
  actions: Actions
}

export default function DisplayTab({ cfg, draft, actions }: Props) {
  return (
    <>
      <PlacementCard
        context="loop"
        title={__('Product lists', 'woocommerce-parcelas')}
        description={__('The shop, category and search pages, and lists such as related products.', 'woocommerce-parcelas')}
        hooks={cfg.choices.loopHooks}
        cfg={cfg}
        draft={draft}
        actions={actions}
      />
      <PlacementCard
        context="single"
        title={__('Product page', 'woocommerce-parcelas')}
        description={__('When variations cost different amounts, the chosen variation’s own lines also appear under its price.', 'woocommerce-parcelas')}
        hooks={cfg.choices.singleHooks}
        cfg={cfg}
        draft={draft}
        actions={actions}
      />
    </>
  )
}

function PlacementCard({ context, title, description, hooks, cfg, draft, actions }: Props & { context: Context; title: string; description: string; hooks: Choice[] }) {
  const placement = draft.placement[context]
  return (
    <Card>
      <CardHeader>
        <CardTitle render={<h2 />}>{title}</CardTitle>
      </CardHeader>
      <CardContent className="tw:flex tw:flex-col tw:gap-4">
        <CardDescription>{description}</CardDescription>
        <div className="tw:grid tw:gap-4 tw:sm:grid-cols-2">
          <Field label={__('Position', 'woocommerce-parcelas')} className="tw:sm:col-span-2">
            {(props) => <ChoiceSelect {...props} choices={hooks} value={placement.hook} onChange={(hook) => actions.placement(context, { hook })} />}
          </Field>
          <Field label={__('Priority', 'woocommerce-parcelas')} hint={__('Order within the position: WooCommerce shows the price at 10, so 15 comes right after it.', 'woocommerce-parcelas')}>
            {(props) => <Input {...props} type="number" step={1} value={String(placement.priority)} onChange={(event) => actions.placement(context, { priority: event.target.value })} />}
          </Field>
          <Field label={__('Alignment', 'woocommerce-parcelas')}>
            {(props) => <ChoiceSelect {...props} choices={cfg.choices.alignments} value={placement.align} onChange={(align) => actions.placement(context, { align })} />}
          </Field>
        </div>
      </CardContent>
    </Card>
  )
}
