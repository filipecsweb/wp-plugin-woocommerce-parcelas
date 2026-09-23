<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Foundation\Security;

/**
 * @since 2.0.0
 */
final class Nonce
{
    /**
     * @since 2.0.0
     */
    public function create(string $action): string
    {
        return wp_create_nonce($action);
    }

    /**
     * @since 2.0.0
     */
    public function verify(string $nonce, string $action): bool
    {
        return wp_verify_nonce($nonce, $action) !== false;
    }

    /**
     * @since 2.0.0
     */
    public function field(string $action, string $name = '_wpnonce', bool $referer = true): string
    {
        return wp_nonce_field($action, $name, $referer, false);
    }
}
