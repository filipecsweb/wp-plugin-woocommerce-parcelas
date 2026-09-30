<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Providers;

use InstallmentPricesForWooCommerce\Foundation\Plugin;
use InstallmentPricesForWooCommerce\Foundation\Provider\ServiceProvider;
use InstallmentPricesForWooCommerce\Foundation\Settings\Options;
use InstallmentPricesForWooCommerce\Lifecycle\WooCommerceCompatibility;
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
     *
     * @var list<class-string>
     */
    protected array $subscribers = [WooCommerceCompatibility::class];

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
     * @since 2.0.0
     */
    public function boot(): void
    {
        parent::boot();

        $this->container->make(Settings::class)->install();
    }
}
