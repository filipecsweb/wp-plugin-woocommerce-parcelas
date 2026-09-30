<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Lifecycle;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use InstallmentPricesForWooCommerce\Foundation\Hooks\Action;
use InstallmentPricesForWooCommerce\Foundation\Plugin;

/**
 * WHY: WooCommerce warns about any plugin that hasn't declared itself compatible with
 * its order tables and checkout blocks. This plugin touches neither.
 *
 * @since 2.0.0
 */
final class WooCommerceCompatibility
{
    /**
     * @since 2.0.0
     */
    public function __construct(private readonly Plugin $plugin)
    {
    }

    /**
     * @since 2.0.0
     */
    #[Action('before_woocommerce_init')]
    public function declareCompatibility(): void
    {
        if (class_exists(FeaturesUtil::class)) {
            FeaturesUtil::declare_compatibility('custom_order_tables', $this->plugin->file(), true);
            FeaturesUtil::declare_compatibility('cart_checkout_blocks', $this->plugin->file(), true);
        }
    }
}
