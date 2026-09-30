<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Module\AdminUi;

use InstallmentPricesForWooCommerce\Foundation\Container\Container;
use InstallmentPricesForWooCommerce\Foundation\Contracts\ModuleInterface;
use InstallmentPricesForWooCommerce\Foundation\Contracts\ServiceProviderInterface;

/**
 * Gated to is_admin() so menu/asset work never runs on front-end requests.
 *
 * @since 2.0.0
 */
final class AdminUiModule implements ModuleInterface
{
    /**
     * @since 2.0.0
     *
     * @param list<class-string<ServiceProviderInterface>> $providers
     */
    public function __construct(private readonly array $providers)
    {
    }

    /**
     * @since 2.0.0
     */
    public function name(): string
    {
        return 'admin-ui';
    }

    /**
     * @since 2.0.0
     */
    public function providers(): array
    {
        return $this->providers;
    }

    /**
     * @since 2.0.0
     */
    public function isEnabled(Container $container): bool
    {
        return is_admin();
    }
}
