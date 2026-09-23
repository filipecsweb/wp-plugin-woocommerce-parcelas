/**
 * General tab: what the installment and cash price lines say.
 *
 * @since 2.0.0
 */
import { __, sprintf } from '@wordpress/i18n'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/ui/card'
import { Input } from '@/ui/input'
import { CheckboxField, ChoiceSelect, Field } from './fields'
import type { Actions, Config, Settings } from './store'

interface Props {
  cfg: Config
  draft: Settings
  actions: Actions
}

export default function GeneralTab({ cfg, draft, actions }: Props) {
  const { installments, cash } = draft
  const inCurrency = (label: string) =>
    /* translators: 1: a field label; 2: the store's currency symbol. */
    sprintf(__('%1$s (%2$s)', 'woocommerce-parcelas'), label, cfg.currency)

  return (
    <>
      <Card>
        <CardHeader>
          <CardTitle render={<h2 />}>{__('Installment price', 'woocommerce-parcelas')}</CardTitle>
        </CardHeader>
        <CardContent className="tw:flex tw:flex-col tw:gap-4">
          <CardDescription>{__('Splits the price into interest-free installments, e.g. "Up to 10 installments of $9.90 interest-free".', 'woocommerce-parcelas')}</CardDescription>
          <CheckboxField label={__('Show the installment price', 'woocommerce-parcelas')} checked={installments.enabled} onChange={(enabled) => actions.installments({ enabled })} />
          <div className="tw:grid tw:gap-4 tw:sm:grid-cols-2">
            <Field label={__('Text before', 'woocommerce-parcelas')} hint={__('e.g. "Up to". Products with a price range say "From" instead.', 'woocommerce-parcelas')}>
              {(props) => <Input {...props} value={installments.prefix} onChange={(event) => actions.installments({ prefix: event.target.value })} />}
            </Field>
            <Field label={__('Text after', 'woocommerce-parcelas')} hint={__('e.g. "interest-free".', 'woocommerce-parcelas')}>
              {(props) => <Input {...props} value={installments.suffix} onChange={(event) => actions.installments({ suffix: event.target.value })} />}
            </Field>
            <Field label={__('Maximum installments', 'woocommerce-parcelas')} hint={__('At least 2. A product can set its own in its Installments tab.', 'woocommerce-parcelas')}>
              {(props) => <Input {...props} type="number" min={2} step={1} value={String(installments.max)} onChange={(event) => actions.installments({ max: event.target.value })} />}
            </Field>
            <Field label={inCurrency(__('Minimum installment', 'woocommerce-parcelas'))} hint={__('Fewer installments are offered when one would cost less. 0 turns the limit off.', 'woocommerce-parcelas')}>
              {(props) => <Input {...props} type="number" min={0} step="any" value={String(installments.min_amount)} onChange={(event) => actions.installments({ min_amount: event.target.value })} />}
            </Field>
          </div>
          <CheckboxField label={__('Show it on out-of-stock products too', 'woocommerce-parcelas')} checked={installments.out_of_stock} onChange={(out_of_stock) => actions.installments({ out_of_stock })} />
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle render={<h2 />}>{__('Cash price', 'woocommerce-parcelas')}</CardTitle>
        </CardHeader>
        <CardContent className="tw:flex tw:flex-col tw:gap-4">
          <CardDescription>{__('A discounted price for paying the full amount at once, e.g. "or $89.10 by bank transfer".', 'woocommerce-parcelas')}</CardDescription>
          <CheckboxField label={__('Show the cash price', 'woocommerce-parcelas')} checked={cash.enabled} onChange={(enabled) => actions.cash({ enabled })} />
          <div className="tw:grid tw:gap-4 tw:sm:grid-cols-2">
            <Field label={__('Text before', 'woocommerce-parcelas')} hint={__('e.g. "or".', 'woocommerce-parcelas')}>
              {(props) => <Input {...props} value={cash.prefix} onChange={(event) => actions.cash({ prefix: event.target.value })} />}
            </Field>
            <Field label={__('Text after', 'woocommerce-parcelas')} hint={__('e.g. "by bank transfer".', 'woocommerce-parcelas')}>
              {(props) => <Input {...props} value={cash.suffix} onChange={(event) => actions.cash({ suffix: event.target.value })} />}
            </Field>
            <Field label={__('Discount', 'woocommerce-parcelas')} hint={__('A product can set its own in its Installments tab.', 'woocommerce-parcelas')}>
              {(props) => <Input {...props} type="number" min={0} step="any" value={String(cash.discount)} onChange={(event) => actions.cash({ discount: event.target.value })} />}
            </Field>
            <Field label={__('Discount type', 'woocommerce-parcelas')}>
              {(props) => (
                <ChoiceSelect
                  {...props}
                  choices={cfg.choices.discountTypes.map((choice) => (choice.value === 'fixed' ? { ...choice, label: inCurrency(choice.label) } : choice))}
                  value={cash.discount_type}
                  onChange={(discount_type) => actions.cash({ discount_type })}
                />
              )}
            </Field>
          </div>
          <CheckboxField label={__('Show it on out-of-stock products too', 'woocommerce-parcelas')} checked={cash.out_of_stock} onChange={(out_of_stock) => actions.cash({ out_of_stock })} />
        </CardContent>
      </Card>
    </>
  )
}
