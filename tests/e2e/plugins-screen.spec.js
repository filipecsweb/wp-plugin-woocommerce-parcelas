import { test, expect } from './support/fixtures.js'
import { PLUGINS_PATH } from './support/config.js'

// Located by href, not label, so the spec holds under any site locale.
test.describe('Plugins screen', () => {
  test('the plugin row links to the settings page ahead of Deactivate', async ({ admin, settings }) => {
    await admin.goto(PLUGINS_PATH)

    // data-plugin, not data-slug: core derives the slug from the plugin name unless wp.org lists the plugin.
    const row = admin.locator('tr[data-plugin="woocommerce-parcelas/woocommerce-parcelas.php"]')
    await expect(row).toBeVisible()

    const actions = row.locator('.row-actions')
    const hrefs = await actions.locator('a').evaluateAll((as) => as.map((a) => a.getAttribute('href') || ''))
    const settingsIndex = hrefs.findIndex((h) => h.includes('admin.php?page=installment-prices-for-woocommerce'))
    const deactivateIndex = hrefs.findIndex((h) => h.includes('action=deactivate'))
    expect(settingsIndex).toBeGreaterThanOrEqual(0)
    expect(deactivateIndex).toBeGreaterThan(settingsIndex)

    await actions.locator('a[href*="page=installment-prices-for-woocommerce"]').click()
    await expect(settings.heading).toBeVisible()
  })
})
