/**
 * Env-derived configuration for the e2e suite. Everything environment-specific is
 * read from `.claude/.env` BY KEY here, so no spec hardcodes a URL or credential.
 * loadEnv() must have run first (the Playwright config calls it at import time;
 * global-setup re-calls it defensively).
 */
const WP_PREFIX = process.env.WP_PATH_PREFIX || ''

export const LOGIN_PATH = `${WP_PREFIX}/wp-login.php`
export const SETTINGS_PATH = `${WP_PREFIX}/wp-admin/admin.php?page=installment-prices-for-woocommerce`
export const PLUGINS_PATH = `${WP_PREFIX}/wp-admin/plugins.php`
export const editProductPath = (id) => `${WP_PREFIX}/wp-admin/post.php?post=${id}&action=edit`

// Storefront paths go through query vars, so they work under any permalink structure.
export const productPath = (slug) => `/?product=${slug}`
export const SHOP_PATH = '/?post_type=product'

export const ADMIN = {
  login: process.env.WP_ADMIN_USER || '',
  pass: process.env.WP_ADMIN_PASS || '',
}

// NS mirrors the PHP source of truth (RestServiceProvider::NAMESPACE).
export const NS = 'installment-prices-for-woocommerce/v1'

/** CONTRACT: tests/e2e/setup-site.sh creates these products. */
export const PRODUCTS = {
  simple: { slug: 'installment-prices-e2e-simple', name: 'Installment Prices E2E Simple' },
  outOfStock: { slug: 'installment-prices-e2e-out-of-stock', name: 'Installment Prices E2E Out of Stock' },
  variable: { slug: 'installment-prices-e2e-variable', name: 'Installment Prices E2E Variable' },
}
