import { test, expect } from './support/fixtures.js'

test.describe('Save settings', () => {
  test('edits on every tab are saved together and survive a reload', async ({ admin, api, settings, restore }) => {
    void restore
    const installments = settings.card('Installment price')

    await installments.getByLabel('Text before').fill('Pay in up to')
    await installments.getByLabel('Maximum installments').fill('12')
    await expect(settings.unsavedNote).toBeVisible()

    await settings.displayTab.click()
    await settings.card('Product page').getByLabel('Alignment').selectOption('center')

    await settings.styleTab.click()
    await settings.root.getByRole('textbox', { name: 'Product page, Amount: Color', exact: true }).first().fill('#cc1818')

    await settings.save()
    await expect(settings.unsavedNote).toBeHidden()

    const saved = await api.settings()
    expect(saved.installments).toMatchObject({ prefix: 'Pay in up to', max: 12 })
    expect(saved.placement.single.align).toBe('center')
    expect(saved.style.installments.single.amount.color).toBe('#cc1818')

    await admin.reload()
    await settings.generalTab.click()
    await expect(settings.card('Installment price').getByLabel('Text before')).toHaveValue('Pay in up to')
  })

  test('the server corrects what it cannot store, and says so for styles', async ({ api, settings, restore }) => {
    void restore
    await settings.card('Installment price').getByLabel('Maximum installments').fill('1')
    await settings.styleTab.click()
    await settings.root.getByRole('textbox', { name: 'Product lists, Text before: Size', exact: true }).first().fill('18 pixels')

    await settings.save()

    await expect(settings.errorToast).toContainText('weren’t valid')
    const saved = await api.settings()
    expect(saved.installments.max).toBe(2)
    expect(saved.style.installments.loop.prefix.size).toBe('')
  })
})
