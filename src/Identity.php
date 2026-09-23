<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce;

/**
 * WHY a slug apart from the text domain: wordpress.org keeps this plugin's original
 * slug (woocommerce-parcelas), which the text domain must match, while everything the
 * code names (options, meta, hooks, asset handles, REST routes) follows its current
 * name.
 *
 * @since 2.0.0
 */
final class Identity
{
    /**
     * @since 2.0.0
     */
    public const SLUG = 'installment-prices-for-woocommerce';
}
