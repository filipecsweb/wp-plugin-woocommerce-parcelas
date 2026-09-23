import { expect } from '@playwright/test'

/**
 * Page Object for the settings screen. It reads state only through roles, labels,
 * `data-testid` and the root's `data-*` attributes (`attr()`), never through the
 * framework's internals.
 */
export class SettingsPage {
  constructor(page) {
    this.page = page
    this.root = page.locator('.installment-prices-admin')

    this.heading = page.getByRole('heading', { name: 'Installment Prices for WooCommerce' })
    this.generalTab = page.getByRole('tab', { name: 'General' })
    this.displayTab = page.getByRole('tab', { name: 'Display' })
    this.styleTab = page.getByRole('tab', { name: 'Style' })
    this.saveButton = this.root.getByRole('button', { name: /^(Save settings|Saving…)$/ })
    this.unsavedNote = this.root.getByText('You have unsaved changes.')

    // Transient toasts, by test id.
    this.errorToast = this.root.getByTestId('toast-error')
    this.successToast = this.root.getByTestId('toast-success')
  }

  /** A card on the current tab, by its heading. */
  card(title) {
    return this.root.locator('[data-slot="card"]').filter({ has: this.page.getByRole('heading', { name: title, exact: true }) })
  }

  /** A root `data-*` attribute. Throws when the screen doesn't render it, so a gap in the contract fails loudly. */
  async attr(name) {
    const value = await this.root.getAttribute(`data-${name}`)
    if (value === null) throw new Error(`[e2e] The settings root has no data-${name} attribute.`)
    return value
  }

  async save() {
    await this.saveButton.click()
    await expect(this.successToast).toContainText('Settings saved.')
    await expect(this.root).toHaveAttribute('data-busy-save', 'false')
  }
}
