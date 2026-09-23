import { test, expect } from './support/fixtures.js'
import { PRODUCTS, SHOP_PATH, editProductPath, productPath } from './support/config.js'

// The fixture prices (tests/e2e/setup-site.sh): simple 100, out of stock 40, variations 50 and 80.
const SETTINGS = {
  installments: { enabled: true, prefix: 'Up to', max: 10, suffix: 'interest-free', min_amount: 5, out_of_stock: false },
  cash: { enabled: true, prefix: 'or', discount: 10, discount_type: 'percent', suffix: 'cash', out_of_stock: false },
  placement: {
    loop: { hook: 'woocommerce_after_shop_loop_item_title', priority: 15, align: '' },
    single: { hook: 'woocommerce_single_product_summary', priority: 15, align: '' },
  },
}

// An amount as the store formats it, whatever its separators and currency.
const amount = (value) => new RegExp(value.replace('.', '[.,]'))

const lines = (scope) => ({
  installments: scope.locator('.installment-prices__installments'),
  cash: scope.locator('.installment-prices__cash'),
})

test.describe('Storefront', () => {
  test.beforeEach(async ({ api }) => {
    await api.saveSettings(SETTINGS)
  })

  test('a product page shows both lines', async ({ admin, restore }) => {
    void restore
    await admin.goto(productPath(PRODUCTS.simple.slug))
    const { installments, cash } = lines(admin.locator('.installment-prices--single'))

    await expect(installments).toContainText('Up to 10 installments of')
    await expect(installments).toContainText(amount('10.00'))
    await expect(installments).toContainText('interest-free')
    await expect(cash).toContainText('or')
    await expect(cash).toContainText(amount('90.00'))
  })

  test('the minimum installment lowers the count', async ({ admin, api, restore }) => {
    void restore
    await api.saveSettings({ installments: { min_amount: 30 } })
    await admin.goto(productPath(PRODUCTS.simple.slug))

    await expect(lines(admin.locator('.installment-prices--single')).installments).toContainText('3 installments of')
  })

  test('product lists show the lines too, "From" for a price range', async ({ admin, restore }) => {
    void restore
    await admin.goto(SHOP_PATH)

    const simple = admin.locator('li', { hasText: PRODUCTS.simple.name }).locator('.installment-prices--loop')
    await expect(lines(simple).installments).toContainText('Up to 10 installments of')
    const variable = admin.locator('li', { hasText: PRODUCTS.variable.name }).locator('.installment-prices--loop')
    await expect(lines(variable).installments).toContainText('From 10 installments of')
    await expect(lines(variable).installments).toContainText(amount('5.00'))
  })

  test('a chosen variation shows its own lines', async ({ admin, restore }) => {
    void restore
    await admin.goto(productPath(PRODUCTS.variable.slug))
    await admin.getByLabel('Size').selectOption('Large')

    const variation = admin.locator('.woocommerce-variation-price .installment-prices--single')
    await expect(lines(variation).installments).toContainText(amount('8.00'))
    await expect(lines(variation).cash).toContainText(amount('72.00'))
  })

  test('out-of-stock products show the lines only when allowed', async ({ admin, api, restore }) => {
    void restore
    await admin.goto(productPath(PRODUCTS.outOfStock.slug))
    await expect(admin.locator('.installment-prices--single')).toHaveCount(0)

    await api.saveSettings({ installments: { out_of_stock: true } })
    await admin.reload()
    // 40 over 10 installments would be 4.00, under the 5.00 minimum, so it drops to 8.
    await expect(lines(admin.locator('.installment-prices--single')).installments).toContainText('Up to 8 installments of')
    await expect(lines(admin.locator('.installment-prices--single')).installments).toContainText(amount('5.00'))
    await expect(lines(admin.locator('.installment-prices--single')).cash).toHaveCount(0)
  })

  test('the style settings reach the page', async ({ admin, api, restore }) => {
    void restore
    await api.saveSettings({ style: { installments: { single: { prefix: { color: '#cc1818', weight: '700' } } } } })
    await admin.goto(productPath(PRODUCTS.simple.slug))

    const prefix = admin.locator('.installment-prices--single .installment-prices__installments .installment-prices__prefix')
    await expect(prefix).toHaveCSS('color', 'rgb(204, 24, 24)')
    await expect(prefix).toHaveCSS('font-weight', '700')
  })

  test('switching both lines off prints nothing', async ({ admin, api, restore }) => {
    void restore
    await api.saveSettings({ installments: { enabled: false }, cash: { enabled: false } })
    await admin.goto(productPath(PRODUCTS.simple.slug))

    await expect(admin.locator('.installment-prices')).toHaveCount(0)
  })

  test('a product can set its own maximum and hide its cash price', async ({ admin, api, restore }) => {
    void restore
    const id = await api.productId(PRODUCTS.simple.slug)
    const edit = async (fill) => {
      await admin.goto(editProductPath(id))
      // Meta boxes that load late shift the editor's layout until they settle.
      await admin.waitForLoadState('networkidle')
      await admin.locator('.installment_prices_options a').click()
      await fill(admin.locator('#installment_prices_product_data'))
      // WordPress redirects to the editor with ?message= once the product is saved.
      await Promise.all([admin.waitForURL(/[?&]message=\d+/), admin.locator('#publish').click()])
    }

    try {
      await edit(async (panel) => {
        await panel.getByLabel('Maximum installments').fill('4')
        await panel.getByLabel('Hide cash price').check()
      })
      await admin.goto(productPath(PRODUCTS.simple.slug))
      const { installments, cash } = lines(admin.locator('.installment-prices--single'))
      await expect(installments).toContainText('Up to 4 installments of')
      await expect(installments).toContainText(amount('25.00'))
      await expect(cash).toHaveCount(0)
    } finally {
      await edit(async (panel) => {
        await panel.getByLabel('Maximum installments').fill('')
        await panel.getByLabel('Hide cash price').uncheck()
      })
    }
  })
})
