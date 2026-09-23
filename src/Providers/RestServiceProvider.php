<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Providers;

use InstallmentPricesForWooCommerce\Foundation\Provider\ServiceProvider;
use InstallmentPricesForWooCommerce\Foundation\Security\Capability;
use InstallmentPricesForWooCommerce\Rest\SettingsController;
use InstallmentPricesForWooCommerce\Settings\Settings;

/**
 * @since 2.0.0
 */
final class RestServiceProvider extends ServiceProvider
{
    /**
     * @since 2.0.0
     */
    public const NAMESPACE = 'installment-prices-for-woocommerce/v1';

    /**
     * The capability that manages this plugin: WooCommerce gives it to shop managers
     * and administrators. Single source for the REST guard and the settings screen
     * (SettingsPage::capability()).
     *
     * @since 2.0.0
     */
    public const CAPABILITY = 'manage_woocommerce';

    /**
     * @since 2.0.0
     */
    public function register(): void
    {
        $container = $this->container;

        $container->singleton(SettingsController::class, static fn (): SettingsController => new SettingsController(
            self::NAMESPACE,
            $container->make(Capability::class),
            $container->make(Settings::class),
        ));
    }

    /**
     * @since 2.0.0
     */
    public function boot(): void
    {
        $this->container->make(SettingsController::class)->hook();
    }
}
