import { test, expect } from './support/fixtures.js'

/**
 * The bundled pt_BR translations reach the screen for a user whose locale is pt_BR:
 * PHP strings through the .mo, React strings through the JSON next to it, which
 * WordPress finds by the md5 of the built entry's path (see README → Translations).
 * One label from each proves the chain; the site needs the pt_BR core language pack.
 */
test.describe('Bundled translations', () => {
  test('the screen follows the user locale (pt_BR)', async ({ admin, api, settings }) => {
    await api.setUserLocale('pt_BR')
    try {
      await admin.reload()
      await expect(admin.getByRole('heading', { name: 'Parcelas para WooCommerce' })).toBeVisible()
      await expect(settings.root.getByRole('button', { name: 'Salvar configurações' })).toBeVisible()
    } finally {
      await api.setUserLocale('')
    }
  })
})
