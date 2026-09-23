import { test, expect } from './support/fixtures.js'

// wp-admin chrome around the screen: toolbar, menu, page heading + description, footer.
const CHROME = ['#wpadminbar', '#adminmenu a', '.wrap > h1', '.wrap > p.description', '#wpfooter']

test.describe('settings shell', () => {
  test('renders the three tabs, the General tab first', async ({ settings }) => {
    await expect(settings.heading).toBeVisible()
    await expect(settings.generalTab).toHaveAttribute('aria-selected', 'true')
    await expect(settings.card('Installment price')).toBeVisible()
    await expect(settings.card('Cash price')).toBeVisible()
    await expect(settings.saveButton).toBeEnabled()
  })

  test('switches tabs and reopens the hash tab on reload', async ({ admin, settings }) => {
    await settings.styleTab.click()
    await expect(settings.styleTab).toHaveAttribute('aria-selected', 'true')
    await expect(admin).toHaveURL(/#style$/)

    await admin.reload()
    await expect(settings.styleTab).toHaveAttribute('aria-selected', 'true')
    await expect(settings.generalTab).toHaveAttribute('aria-selected', 'false')
  })

  test('leaves the wp-admin chrome untouched', async ({ admin }) => {
    const chromeStyles = () =>
      admin.evaluate(
        (selectors) =>
          selectors.flatMap((selector) => {
            const style = getComputedStyle(document.querySelector(selector))
            // Custom properties are skipped: Tailwind's @property registrations are global by spec and paint nothing.
            return [...style].filter((prop) => !prop.startsWith('--')).map((prop) => `${selector} ${prop}: ${style.getPropertyValue(prop)}`)
          }),
        CHROME
      )

    // WHY: the screen's own height depends on our CSS, and it moves the footer's resolved `top`. Hide it for both reads.
    await admin.evaluate(() => document.querySelectorAll('.wrap > :not(h1, p.description)').forEach((el) => (el.style.display = 'none')))

    const withOurCss = await chromeStyles()
    const disabled = await admin.evaluate(() => {
      const links = [...document.querySelectorAll('link[rel="stylesheet"][href*="/public/build/"]')]
      links.forEach((link) => (link.disabled = true))
      return links.length
    })
    const withoutOurCss = await chromeStyles()

    expect(disabled).toBeGreaterThan(0)
    expect(withOurCss.filter((line) => !withoutOurCss.includes(line))).toEqual([])
  })

  test('logs no console error of its own', async ({ admin, settings }) => {
    // Other plugins' scripts share the page; only errors raised from this plugin's bundle count.
    const errors = []
    admin.on('console', (message) => message.type() === 'error' && errors.push(`${message.location().url} ${message.text()}`))
    admin.on('pageerror', (error) => errors.push(error.stack ?? error.message))

    await admin.reload()
    for (const tab of [settings.displayTab, settings.styleTab, settings.generalTab]) {
      await tab.click()
      await expect(tab).toHaveAttribute('aria-selected', 'true')
    }

    expect(errors.filter((text) => text.includes('/woocommerce-parcelas/public/build/'))).toEqual([])
  })
})
