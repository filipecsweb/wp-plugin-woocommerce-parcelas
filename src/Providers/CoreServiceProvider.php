<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Providers;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use InstallmentPricesForWooCommerce\Foundation\Plugin;
use InstallmentPricesForWooCommerce\Foundation\Provider\ServiceProvider;
use InstallmentPricesForWooCommerce\Foundation\Settings\Options;
use InstallmentPricesForWooCommerce\Product\Overrides;
use InstallmentPricesForWooCommerce\Settings\Settings;
use InstallmentPricesForWooCommerce\Settings\StorageKeys;

/**
 * @since 2.0.0
 */
final class CoreServiceProvider extends ServiceProvider
{
    /**
     * @since 2.0.0
     */
    public function register(): void
    {
        $container = $this->container;
        $prefix    = $container->make(Plugin::class)->optionPrefix();

        $container->singleton(Options::class, static fn (): Options => new Options(StorageKeys::settings($prefix)));
        $container->singleton(Settings::class);
        $container->singleton(Overrides::class, static fn (): Overrides => new Overrides(StorageKeys::productMeta($prefix)));
    }

    /**
     * WHY the declarations: WooCommerce warns about any plugin that hasn't declared
     * itself compatible with its order tables and checkout blocks. This plugin
     * touches neither.
     *
     * @since 2.0.0
     */
    public function boot(): void
    {
        $this->container->make(Settings::class)->install();

        $file = $this->container->make(Plugin::class)->file();

        add_action('before_woocommerce_init', static function () use ($file): void {
            if (class_exists(FeaturesUtil::class)) {
                FeaturesUtil::declare_compatibility('custom_order_tables', $file, true);
                FeaturesUtil::declare_compatibility('cart_checkout_blocks', $file, true);
            }
        });
    }
}
