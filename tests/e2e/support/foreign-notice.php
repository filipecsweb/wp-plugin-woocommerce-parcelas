<?php
/**
 * Plugin Name: Installment Prices for WooCommerce E2E foreign notice
 * Description: Test fixture from tests/e2e/setup-site.sh. Prints an admin notice only for a request carrying its cookie, so it is inert for anyone else on the site.
 *
 * CONTRACT: shell.spec.js sets the cookie and looks up the notice by this id.
 */
add_action('admin_notices', static function (): void {
    if (isset($_COOKIE['installment_prices_for_woocommerce_e2e_notice'])) {
        echo '<div id="installment-prices-for-woocommerce-e2e-notice" class="notice notice-warning"><p>E2E foreign notice</p></div>';
    }
});
