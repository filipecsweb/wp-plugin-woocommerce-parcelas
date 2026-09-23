# Foundation Modules

Modules are **opt-in units** that extend the pure Foundation kernel. They live
here, _outside_ `foundation/`, so that copying `foundation/` alone yields a clean
kernel with **zero modules attached**. Each module is independently copyable: take
the module folder you want, add its PSR-4 root to `composer.json`, and register it.

## The contract

Every module implements [`InstallmentPricesForWooCommerce\Foundation\Contracts\ModuleInterface`](../foundation/src/Contracts/ModuleInterface.php):

```php
interface ModuleInterface
{
    public function name(): string;                 // unique slug, e.g. "admin-ui"
    public function providers(): array;             // list<class-string<ServiceProviderInterface>>
    public function isEnabled(Container $c): bool;   // load only when relevant (e.g. is_admin())
}
```

A module contributes one or more **service providers** to the kernel. The kernel
runs each provider's `register()` (bind services) then `boot()` (attach hooks),
exactly like first-party providers.

## PSR-4 convention

```jsonc
// composer.json
"autoload": {
  "psr-4": {
    "InstallmentPricesForWooCommerce\\Module\\AdminUi\\": "modules/admin-ui/src/"
    // "InstallmentPricesForWooCommerce\\Module\\Blocks\\": "modules/blocks/src/"  ← when you add it
  }
}
```

## Implemented in this build

| Module     | Path                 | Namespace                  | Status |
|------------|----------------------|----------------------------|--------|
| `admin-ui` | `modules/admin-ui/`  | `InstallmentPricesForWooCommerce\Module\AdminUi\`  | ✅ Built |

> **admin-ui ships no CSS of its own.** `AdminAssets` scopes a Vite-built bundle
> (plus its core-script dependencies and JSON translations) to one screen; how that
> bundle keeps wp-admin untouched is the plugin's call. This plugin's screen
> (`resources/css/app.css`) scopes a full reset plus Tailwind v4 (`tw` prefix,
> utilities `important`) to its React mount, so nothing outside the mount is restyled.

## Planned extension points (NOT implemented)

Each would live as `modules/<name>/src/` under `InstallmentPricesForWooCommerce\Module\<Name>\` and
implement `ModuleInterface`. They are documented here as the seams where future
work plugs in — there is no stub code for them, only this contract.

| Module          | Purpose                                                        |
|-----------------|---------------------------------------------------------------|
| `blocks`        | `@wordpress/scripts` + React + `block.json` editor blocks.     |
| `interactivity` | WordPress Interactivity API for frontend reactivity.           |
| `cpt-tax`       | Custom post type / taxonomy registration helpers.              |
| `woocommerce`   | HPOS-compatible WooCommerce extension scaffold.                |
| `jobs`          | Action Scheduler integration for background jobs.              |
| `rest-api`      | Expanded REST controllers + authentication beyond the base.    |
