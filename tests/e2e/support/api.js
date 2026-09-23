import { expect } from '@playwright/test'
import { NS } from './config.js'

/**
 * REST client for the plugin's own routes (and core's), used by specs to set up,
 * tear down and assert state out-of-band. Built on page.request, which shares the
 * browser's auth cookies (we add the wp_rest nonce).
 */
export class Api {
  constructor(page, root, nonce) {
    this.page = page
    this.root = root
    this.nonce = nonce
  }

  /**
   * The REST root and the wp_rest nonce come from core's own apiFetch bootstrap (the
   * wp-api-fetch inline script), the source the screen's requests use.
   */
  static async forPage(page) {
    const { root, nonce } = await page.evaluate(() => ({
      root: /createRootURLMiddleware\(\s*"([^"]*)"/.exec(document.getElementById('wp-api-fetch-js-after')?.textContent ?? '')?.[1],
      nonce: window.wp?.apiFetch?.nonceMiddleware?.nonce,
    }))
    if (!root || !nonce) throw new Error('[e2e] wp-api-fetch is not bootstrapped on this page.')
    return new Api(page, root, nonce)
  }

  req(method, routePath, body, namespace = NS) {
    return this.page.request[method](`${this.root}${namespace}${routePath}`, {
      headers: { 'X-WP-Nonce': this.nonce, ...(body ? { 'Content-Type': 'application/json' } : {}) },
      ...(body ? { data: body } : {}),
    })
  }

  async settings() {
    const res = await this.req('get', '/settings')
    expect(res.ok(), 'GET /settings').toBeTruthy()
    return res.json()
  }

  /** Any part of the settings object; the rest is kept. Resolves to the full, validated settings. */
  async saveSettings(patch) {
    const res = await this.req('post', '/settings', patch)
    expect(res.ok(), 'POST /settings').toBeTruthy()
    return res.json()
  }

  async productId(slug) {
    const res = await this.req('get', `/products?slug=${encodeURIComponent(slug)}`, undefined, 'wc/v3')
    expect(res.ok(), `GET wc/v3/products?slug=${slug} (run tests/e2e/setup-site.sh)`).toBeTruthy()
    const [product] = await res.json()
    expect(product, `fixture product ${slug} (run tests/e2e/setup-site.sh)`).toBeTruthy()
    return product.id
  }

  /** The admin user's own locale ('' = the site default). Core rejects a locale whose language pack isn't installed. */
  async setUserLocale(locale) {
    const res = await this.req('post', '/users/me', { locale }, 'wp/v2')
    expect(res.ok(), `set the user locale to "${locale}" (wp language core install ${locale})`).toBeTruthy()
  }
}
