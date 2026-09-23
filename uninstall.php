<?php

/**
 * Uninstall routine for Installment Prices for WooCommerce.
 *
 * @package InstallmentPricesForWooCommerce
 * @since 2.0.0
 */

declare(strict_types=1);

use InstallmentPricesForWooCommerce\Foundation\Plugin;
use InstallmentPricesForWooCommerce\Identity;
use InstallmentPricesForWooCommerce\Lifecycle\Uninstaller;

defined('WP_UNINSTALL_PLUGIN') || exit;

// Wrapped in an immediately-invoked closure so the autoload local never enters
// the global scope (WordPress.org Plugin Check flags global-scope plugin vars).
(static function (): void {
    $autoload = __DIR__ . '/vendor/autoload.php';

    if (! is_file($autoload)) {
        return;
    }

    require $autoload;

    // The option prefix comes from the SAME rule the runtime uses
    // (Plugin::optionPrefix()), so the two can never name different rows.
    Uninstaller::uninstall(Plugin::create(__DIR__ . '/woocommerce-parcelas.php', Identity::SLUG)->optionPrefix());
})();
