# CLAUDE.md — Installment Prices for WooCommerce

This plugin is built on the **WPForge** base: a reusable kernel in `foundation/`
(namespace `InstallmentPricesForWooCommerce\Foundation\` — the bundled kernel is scoped under
the plugin's own namespace so every shipped class is unique to this plugin, per wordpress.org's
unique-prefix rule), opt-in features in `modules/` (namespace
`InstallmentPricesForWooCommerce\Module\…`), and this plugin's own code in `src/` (namespace
`InstallmentPricesForWooCommerce\`). The kernel and the admin screen's UI kit are copies of
FastCGI Cache for Ploi's (`~/dev/fastcgi-cache-for-ploi`); keep them diffable against it. Read
this before changing anything. When a rule here conflicts with a habit or a quick shortcut, the
rule wins.

## Two names, on purpose
- **`woocommerce-parcelas`** is the wordpress.org slug, which is permanent: the plugin folder,
  the main file `woocommerce-parcelas.php`, and the **text domain** (wordpress.org requires it
  to match the slug) keep it. Never rename any of them — a renamed main file deactivates the
  plugin on every site at update.
- **`installment-prices-for-woocommerce`** (`Identity::SLUG`) is everything the code names:
  options, product meta, hooks, REST routes, asset handles, CSS classes, the React mount.
  `Plugin::create(__FILE__, Identity::SLUG)` hands it to the kernel.

## Code principles
- **One source of truth.** Every value, rule, or list lives in exactly one place. Need it
  twice? Reference the single definition — never copy. The settings shape, defaults and
  validation live in `Settings`; storage keys in `StorageKeys`.
- **No duplication.** Before adding code, check whether it already exists — in this plugin
  AND in `foundation/`. If it does, reuse it.
- **Reuse the kernel, don't reroll it.** DI container, attribute hooks, typed Options, REST
  base controller with `guard()`, the vendored Vite enqueuer, i18n. Use these.
- **Coherent & consistent.** Match existing patterns, names, and structure.
- **No placeholders.** No TODOs, stubs, "implement later," or magic numbers.

## Comments
Comments explain what the code CAN'T say. Default to none. Write one ONLY for:
- **WHY:** rationale for a non-obvious choice.
- **GOTCHA:** side effects or ordering constraints.
- **CONTRACT:** assumptions the code can't enforce.
- **LINK:** ticket/spec URL, or a browser-bug workaround reference.

NEVER write a comment that restates the code or a well-named symbol, or that would become
false if the code were moved or reused.

## Architecture invariants (do not violate)
- **`foundation/` is a pure kernel.** Only generic primitives + the module contract. Plugin
  code → `src/`. Reusable opt-in features → `modules/`. A kernel fix belongs in fastcgi's
  copy too.
- **Don't restructure silently.** If you need to deviate from the directory or namespace
  layout, FLAG it and say why before doing it.

## Specific rules (each prevents a real, recurring bug)
- **Settings = one autoloaded option row**, written only through `Settings` (whose
  `sanitize()` is the single validator for storage, REST and upgrades).
- **Upgrades run on a request, not on activation**: WordPress doesn't fire activation hooks
  on updates. `Settings::install()` stores 1.x's settings (or the defaults) the first time.
  1.x data is read, never deleted, until uninstall.
- **Uninstall purges everything**, 1.x's option and product meta included (`Uninstaller`).
- **Git branches:** never create or switch branches unless the user explicitly asks.
- **Hooks via attributes**, except where the hook name is only known at runtime — the
  storefront positions (settings) and `plugin_action_links_<basename>` — which are wired in
  their provider with a WHY comment.
- **Every user-facing toggle gates its hooks.** With both lines off, the storefront provider
  hooks nothing.
- **REST only, one guard.** Admin endpoints use `register_rest_route` behind the shared
  `guard()` (nonce + `RestServiceProvider::CAPABILITY`, `manage_woocommerce`).
- **Escape late.** Storefront HTML is built from escaped parts and printed through
  `wp_kses()` with `Renderer::allowedHtml()` (post HTML plus what `wc_price()` adds). Inline
  CSS only ever holds values `Settings::sanitize()` admitted.
- **i18n:** never call `__()`/`_e()` before the `init` hook — `Settings::choices()` is
  translated, so only the admin screen calls it. Text domain = `woocommerce-parcelas`.

## Admin UI: React inside a scoped reset — wp-admin classes don't work in the mount

The settings screen is React (`resources/js/app`, entry `app/main.tsx`) on shadcn/ui
components (`resources/js/ui`, Base UI primitives, copied from fastcgi), built against the
React and `@wordpress/*` globals core already loads (`wpExternals()` in `vite.config.js`).
PHP prints only the mount `#installment-prices-for-woocommerce-app`
(`SettingsPage::APP_ROOT_ID`). `resources/css/app.css` scopes an unlayered `all: revert`
reset plus preflight to that id, and the `tw:` utilities (v4 CSS-first — `tw:`, not `tw-`)
are layered and `important`, so they beat both the reset and wp-admin.

Consequences (each one has bitten):

- **No wp-admin classes or dashicons inside the mount.** Use the shadcn components and
  lucide icons.
- **Inline styles lose to `tw:` utilities** (they are `important`). Style with utilities.
- **No `rem` in arbitrary values**: a rem follows the page's root font size, which the
  isolation spec changes on purpose. Use px.
- **Everything portalled (Tooltip, Toast, Dialog) renders into the shared container**
  from `usePortalContainer()` (`ui/portal.tsx`).
- **Core ships React 18:** a component passed to `render=` or given a ref needs
  `React.forwardRef`. `check:build` (part of `qa:js`) fails on a bundled React copy or on a
  CSS selector outside the mount — never weaken it.
- **Strings:** `__()` from `@wordpress/i18n` with the plugin text domain, only in `app/`
  (and the kit's `ui/`). After a string change, run README → Translations.
- **Root contract:** React renders `.installment-prices-admin` with the `data-*` attributes
  as plain props; the E2E page object (`tests/e2e/support/settings-page.js`) reads only
  roles, labels, `data-testid` and those attributes.

The product editor's **Installments** tab is not React: it uses WooCommerce's own field
helpers (`ProductDataTab`), so it matches the tabs around it.

Definition of done for any change: `composer qa`, `npm run qa:js`, and the E2E suite green
against a WordPress with WooCommerce (`npm run e2e`).
Dev loop: `npm run watch` (no dev server: the externals are build-only).

<!-- contract:claude-contract-block -->
## Project contract

- **[`CONTRIBUTING.md`](CONTRIBUTING.md) is binding** — read it before working in this repo; follow it exactly.
- Blocks between `<!-- contract:* -->` markers and bootstrap-installed files are owned by the cross-project contract that generated them. Never edit them here; change the contract, then re-bootstrap.
<!-- /contract:claude-contract-block -->
