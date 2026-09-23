# Installment Prices for WooCommerce

Show installment prices and a discounted cash price on WooCommerce product lists
and product pages:

> Up to 10 installments of $9.90 interest-free
> or $89.10 by bank transfer

Published on wordpress.org as [`woocommerce-parcelas`](https://wordpress.org/plugins/woocommerce-parcelas/),
its name until 2.0.0. The slug is permanent, so the plugin folder, the main file
(`woocommerce-parcelas.php`) and the text domain keep it; everything the code names
uses `installment-prices-for-woocommerce` ([`src/Identity.php`](src/Identity.php)).

Built on the same plugin **Foundation** as
[FastCGI Cache for Ploi](https://github.com/filipecsweb/wp-plugin-fastcgi-cache-for-ploi)
(PHP 8.2+, PSR-11 DI container, attribute-based hooks, vendored Vite enqueuer, opt-in
modules), bundled under this plugin's namespace.

---

## What it does

- **Installment price**: the price split into up to N installments, never fewer than
  2, each at or above a minimum amount, with the store's own text before and after.
- **Cash price**: a percentage or fixed discount for paying in full.
- **Placement and style**: the WooCommerce position (and priority) for product lists
  and the product page, alignment, and color / weight / size per part of each line.
- **Per product**: an **Installments** tab in the Product data box hides either line
  or sets the product's own maximum installments and cash discount.
- **Variable and grouped products** show "From" figures; a variable product's chosen
  variation shows its own under its price (appended to the variation's `price_html`).
- **Upgrades from 1.x** carry the settings over on the first request after the update
  (`Settings::install()`), and read each product's 1.x settings until it is saved again.

## Requirements

- PHP **8.2+**, WordPress **7.0+**, WooCommerce **9.0+** (the `Requires Plugins` header
  makes WordPress enforce it)
- For development: Composer, Node (the version pinned in `.nvmrc`), and a local
  WordPress with WooCommerce (e.g. [Herd](https://herd.laravel.com) + [DBngin](https://dbngin.com))

## Installation

The build artifacts (`public/build/`) and Composer dependencies are not committed —
build them once:

```bash
composer install --no-dev   # production autoloader (omit --no-dev for tooling)
npm ci
npm run build               # emits public/build/ (entries + .vite/manifest.json)
```

Then symlink the plugin folder into a site's `wp-content/plugins/` as
`woocommerce-parcelas` and activate it:

```bash
ln -s ~/dev/woocommerce-parcelas ~/Herd/mysite/wp-content/plugins/woocommerce-parcelas
```

The settings live under **WooCommerce → Installment Prices**.

**Rebuilding the admin UI on save:** `npm run watch`. The settings screen is React
(shadcn/ui components) and reads React and `@wordpress/*` from the globals WordPress
already loads, which Vite's dev server can't serve — so there is no HMR, only the
rebuild.

## Developer hooks

Each line's HTML passes through a filter before it prints, with the product and the
context (`loop` in product lists, `single` on the product page):

```php
add_filter( 'installment_prices_for_woocommerce_installments_html', fn ( $html, $product, $context ) => $html, 10, 3 );
add_filter( 'installment_prices_for_woocommerce_cash_html', fn ( $html, $product, $context ) => $html, 10, 3 );
```

## Development & QA

```bash
composer cs      # PHPCS — PSR-12 + WordPress sniffs (not a substitute for Plugin Check)
composer stan    # PHPStan at max level (WordPress + WooCommerce stubs)
composer test    # Pest unit suite (Brain Monkey)
composer qa      # versions + since + all three

npm run build    # production assets
npm run watch    # rebuild on save (no dev server: the screen uses core's React globals)
npm run qa:js    # typecheck + ESLint + Vitest, then build and check the React bundle
                 # (no bundled React copy, no CSS outside the app's mount)
npm run e2e      # Playwright E2E against a real WordPress (see below)
```

**E2E (Playwright against a real WordPress + WooCommerce):**

Point the suite at a local site that serves *this* checkout. Copy
`.claude/.env.example` to `.claude/.env` (auto-loaded) and set:

```bash
WP_BASE_URL=https://your-site.test        # the WordPress under test
WP_ADMIN_USER=admin                       # an admin login
WP_ADMIN_PASS=password
WP_PATH_PREFIX=                           # /wp for a Bedrock install
WP_PLUGIN_PATH=/abs/path/to/site/wp-content/plugins/woocommerce-parcelas
                                          # symlink to this checkout; the preflight
                                          # refuses to run against a stale copy

npm run e2e
```

Prepare the site once with `tests/e2e/setup-site.sh <WordPress path>`: it installs
WooCommerce if needed, the pt_BR core language pack (one spec needs it to prove the
bundled translations load), and the fixture products the storefront specs visit.

CI provisions its own WordPress with WP-CLI — see `.github/workflows/ci.yml` — once
per WordPress version it supports: the plugin header's `Requires at least` and the
latest. It runs `tests/e2e/setup-site.sh` on each fresh install, before activating
the plugin.

**Translations:** `languages/` ships the pt_BR `.po`/`.mo` and the JSON the React
screen loads (`<domain>-<locale>-<md5 of public/build/main.js>.json`, which is why the
built entry keeps a stable, unhashed name). After a string changes:

```bash
npm run build                        # make-pot reads the React strings from the BUILT entry (it doesn't parse TSX)
wp i18n make-pot . languages/woocommerce-parcelas.pot --exclude=dist
wp i18n update-po languages/woocommerce-parcelas.pot languages/
#  ...translate the new msgids in the .po, then:
wp i18n make-mo languages/
wp i18n make-json languages/ --no-purge --pretty-print   # --no-purge keeps the .po as the single source
```

## License

GPL-2.0-or-later.

WooCommerce is a trademark of its respective owner. This plugin is not affiliated with
or endorsed by WooCommerce.
