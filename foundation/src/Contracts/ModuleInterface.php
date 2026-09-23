<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Foundation\Contracts;

use InstallmentPricesForWooCommerce\Foundation\Container\Container;

/**
 * The opt-in module contract.
 *
 * This interface is the ONLY module-related code that lives in the pure
 * Foundation. Module implementations live independently under the top-level
 * modules/ tree (e.g. modules/admin-ui/src) so that copying foundation/ alone
 * yields a clean kernel with zero modules attached. A module declares the
 * service providers it contributes and whether it should load in the current
 * request.
 *
 * @see modules/README.md for the catalogue of available / planned modules.
 *
 * @since 2.0.0
 */
interface ModuleInterface
{
    /**
     * Must be unique and stable across releases (used as a persisted key).
     *
     * @since 2.0.0
     */
    public function name(): string;

    /**
     * Service-provider classes this module contributes to the kernel.
     *
     * @since 2.0.0
     *
     * @return list<class-string<ServiceProviderInterface>>
     */
    public function providers(): array;

    /**
     * @since 2.0.0
     */
    public function isEnabled(Container $container): bool;
}
