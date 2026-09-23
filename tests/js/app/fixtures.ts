import { vi } from 'vitest'
import type { Api } from '@/shared/api'
import type { Config, PartStyle, Settings } from '@/app/store'

const blank: PartStyle = { color: '', weight: '', size: '' }
const context = () => ({ prefix: { ...blank }, amount: { ...blank }, suffix: { ...blank } })

export const settings: Settings = {
  installments: { enabled: true, prefix: 'Up to', max: 10, suffix: 'interest-free', min_amount: 5, out_of_stock: false },
  cash: { enabled: false, prefix: 'or', discount: 10, discount_type: 'percent', suffix: 'cash', out_of_stock: false },
  placement: {
    loop: { hook: 'woocommerce_after_shop_loop_item_title', priority: 15, align: '' },
    single: { hook: 'woocommerce_single_product_summary', priority: 15, align: '' },
  },
  style: { installments: { loop: context(), single: context() }, cash: { loop: context(), single: context() } },
}

const choice = (value: string, label: string) => ({ value, label })

export const cfg: Config = {
  restNamespace: 'ns',
  plugin: { name: 'Installment Prices for WooCommerce', version: '2.0.0' },
  settings,
  choices: {
    loopHooks: [choice('woocommerce_after_shop_loop_item_title', 'Below the product title'), choice('woocommerce_after_shop_loop_item', 'Below the Add to cart button')],
    singleHooks: [choice('woocommerce_single_product_summary', 'In the product summary'), choice('woocommerce_after_add_to_cart_form', 'Below the add-to-cart form')],
    alignments: [choice('', 'Theme default'), choice('center', 'Center')],
    weights: [choice('', 'Theme default'), choice('700', '700 (Bold)')],
    discountTypes: [choice('percent', 'Percentage (%)'), choice('fixed', 'Fixed amount')],
  },
  currency: '$',
  supportUrl: 'https://wordpress.org/support/plugin/woocommerce-parcelas/',
}

// vi.fn<Api>() drops Api's generic, so the mock is typed on its parameters and cast back.
export const mockApi = () => vi.fn<(...args: Parameters<Api>) => Promise<unknown>>()
