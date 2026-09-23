import { test as base, expect } from '@playwright/test'
import { ADMIN, LOGIN_PATH, SETTINGS_PATH } from './config.js'
import { Api } from './api.js'
import { SettingsPage } from './settings-page.js'

async function loginAsAdmin(page) {
  await page.goto(LOGIN_PATH)
  await page.fill('#user_login', ADMIN.login)
  await page.fill('#user_pass', ADMIN.pass)
  await page.click('#wp-submit')
  await expect(page).toHaveURL(/wp-admin/)
}

/**
 * Shared fixtures. Defined once so specs can't drift:
 *  - admin     — logged-in admin sitting on the settings screen.
 *  - settings  — Page Object bound to that page.
 *  - api       — REST client (page.request + the live nonce) for out-of-band state.
 *  - restore   — puts the saved settings back after the test, whatever it changed,
 *                so every spec is independent.
 */
export const test = base.extend({
  admin: async ({ page }, use) => {
    await loginAsAdmin(page)
    await page.goto(SETTINGS_PATH)
    await expect(page.locator('.installment-prices-admin')).toBeVisible()
    await use(page)
  },

  settings: async ({ admin }, use) => {
    await use(new SettingsPage(admin))
  },

  api: async ({ admin }, use) => {
    await use(await Api.forPage(admin))
  },

  restore: async ({ api }, use) => {
    const before = await api.settings()
    await use()
    await api.saveSettings(before)
  },
})

export { expect }
