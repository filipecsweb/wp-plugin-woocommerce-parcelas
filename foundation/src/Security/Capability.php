<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Foundation\Security;

/**
 * @since 2.0.0
 */
final class Capability
{
    /**
     * @since 2.0.0
     */
    public function can(string $capability, mixed ...$args): bool
    {
        return current_user_can($capability, ...$args);
    }
}
