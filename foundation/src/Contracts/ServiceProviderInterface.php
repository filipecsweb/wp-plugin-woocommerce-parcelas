<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Foundation\Contracts;

/**
 * A service provider wires a slice of the plugin into the container.
 *
 * register() runs for ALL providers before any boot() runs, so register()
 * must only bind services (never assume another provider has booted), while
 * boot() may resolve services and attach WordPress hooks.
 *
 * @since 2.0.0
 */
interface ServiceProviderInterface
{
    /**
     * @since 2.0.0
     */
    public function register(): void;

    /**
     * @since 2.0.0
     */
    public function boot(): void;
}
