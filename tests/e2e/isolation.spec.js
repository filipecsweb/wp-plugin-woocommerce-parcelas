import { test, expect } from './support/fixtures.js'

// Everything the screen draws comes from its own tokens: no value set outside the mount, inherited
// or as a variable, may change it. Only the page's direction is meant to flow in. That includes
// another plugin's Tailwind build with the same `tw` prefix, loaded after ours on every admin screen:
// its utilities share our layer and class names.
//
// Out of scope, because CSS offers no way to stop them: a text decoration drawn on an ancestor
// (it paints across descendants' text), paint effects on an ancestor (opacity, filter, transform),
// and @property registrations, which are global by spec.
const HOSTILE = `
  :root {
    --wp-admin-theme-color: #f00; --wp-admin-theme-color--rgb: 255, 0, 0; --wp-admin-border-width-focus: 9px;
    --tw-color-green-100: #f00; --tw-spacing: 1rem; --tw-leading: 5;
    --primary: #f00; --background: #000; --foreground: #0f0; --border: #f00; --radius: 20px;
  }
  html, body, #wpwrap, #wpcontent, #wpbody, #wpbody-content, .wrap {
    color: #f00; font-family: serif; font-size: 20px; font-style: italic; font-weight: 700; line-height: 3;
    letter-spacing: 2px; word-spacing: 4px; text-transform: uppercase; text-align: right; text-indent: 30px;
    text-shadow: 1px 1px #f00; white-space: pre; cursor: crosshair; font-variant: small-caps;
    -webkit-font-smoothing: none; text-rendering: geometricPrecision; hyphens: auto; tab-size: 20;
    word-break: break-all; overflow-wrap: anywhere; list-style: square inside; caret-color: #f00;
  }
  @layer utilities {
    .tw\\:flex { display: none !important; }
  }`

// Every element of the screen with its box size and every standard computed property. Left out:
// custom properties (an ancestor's variable is visible inside, and harmless while nothing reads it)
// and resolved insets, which only say where the screen sits on the page. The hostile CSS moves it
// down, since it restyles the heading above; that is placement, not style.
const PLACEMENT = /^(top|right|bottom|left|inset-.*)$/
const snapshot = (page) =>
  page.evaluate((placement) => {
    const skip = new RegExp(placement)
    const root = document.getElementById('installment-prices-for-woocommerce-app')
    return [root, ...root.querySelectorAll('*')].map((el, i) => {
      const style = getComputedStyle(el)
      const { width, height } = el.getBoundingClientRect()
      const props = [...style].filter((prop) => !prop.startsWith('--') && !skip.test(prop)).map((prop) => `${prop}: ${style.getPropertyValue(prop)}`)
      // Rounded: an SVG path's box moves in its last digits with the sub-pixel origin of the screen.
      return `${i} <${el.tagName.toLowerCase()}> ${width.toFixed(2)}x${height.toFixed(2)} | ${props.join('; ')}`
    })
  }, PLACEMENT.source)

// The tabs fade for a moment after a switch; a snapshot mid-fade would read the wrong values. Only
// the screen's own animations count: other plugins on the page may run endless ones.
const settled = (page) =>
  page.evaluate(() => Promise.all(document.getElementById('installment-prices-for-woocommerce-app').getAnimations({ subtree: true }).map((animation) => animation.finished)))

test.describe('style isolation', () => {
  for (const tab of ['General', 'Display', 'Style']) {
    test(`nothing set outside the mount changes the ${tab} tab`, async ({ admin }) => {
      await admin.getByRole('tab', { name: tab }).click()

      await settled(admin)
      const before = await snapshot(admin)
      await admin.addStyleTag({ content: HOSTILE })
      await settled(admin)
      const after = await snapshot(admin)

      expect(before.length).toBeGreaterThan(20)
      expect(after.filter((line, i) => line !== before[i])).toEqual([])
    })
  }

  test('the page direction still reaches the screen', async ({ admin }) => {
    await admin.evaluate(() => document.documentElement.setAttribute('dir', 'rtl'))

    expect(await admin.locator('.installment-prices-admin').evaluate((el) => getComputedStyle(el).direction)).toBe('rtl')
  })
})
