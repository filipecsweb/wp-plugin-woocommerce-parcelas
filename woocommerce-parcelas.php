<?php

/**
 * Plugin Name:          Installment Prices for WooCommerce
 * Plugin URI:           https://wordpress.org/plugins/woocommerce-parcelas/
 * Description:          Show installment prices and a discounted cash price on your WooCommerce product lists and product pages.
 * Version:              2.0.0
 * Requires at least:    7.0
 * Requires PHP:         8.2
 * Requires Plugins:     woocommerce
 * Author:               Filipe Seabra
 * Author URI:           https://github.com/filipecsweb
 * License:              GPL-2.0-or-later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          woocommerce-parcelas
 * Domain Path:          /languages
 * WC requires at least: 9.0
 * WC tested up to:      11.1
 *
 * @package InstallmentPricesForWooCommerce
 * @since 2.0.0
 *
 * This header is the SINGLE SOURCE OF TRUTH for the plugin version. The Foundation
 * kernel reads it at runtime via get_file_data() — never hardcode the version
 * anywhere else.
 */

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce;

use InstallmentPricesForWooCommerce\Foundation\Plugin;
use InstallmentPricesForWooCommerce\Module\AdminUi\AdminUiModule;
use InstallmentPricesForWooCommerce\Providers\AdminServiceProvider;
use InstallmentPricesForWooCommerce\Providers\CoreServiceProvider;
use InstallmentPricesForWooCommerce\Providers\RestServiceProvider;
use InstallmentPricesForWooCommerce\Providers\StorefrontServiceProvider;

defined('ABSPATH') || exit;

/**
 * Boot the plugin on top of the bundled Foundation kernel.
 *
 * Wrapped in an immediately-invoked closure so the bootstrap locals never enter
 * the global scope (WordPress.org Plugin Check flags global-scope plugin vars).
 */
(static function (): void {
    $autoload = __DIR__ . '/vendor/autoload.php';

    if (! is_file($autoload)) {
        add_action('admin_notices', static function (): void {
            echo '<div class="notice notice-error"><p>';
            echo esc_html__(
                'Installment Prices for WooCommerce: dependencies are missing. Run "composer install" in the plugin directory.',
                'woocommerce-parcelas'
            );
            echo '</p></div>';
        });

        return;
    }

    require $autoload;

    $plugin = Plugin::create(__FILE__, Identity::SLUG);

    $plugin->withProviders([
        CoreServiceProvider::class,
        StorefrontServiceProvider::class,
        RestServiceProvider::class,
    ]);

    $plugin->withModule(new AdminUiModule([
        AdminServiceProvider::class,
    ]));

    add_action('plugins_loaded', static function () use ($plugin): void {
        // The Requires Plugins header keeps WooCommerce active alongside this plugin;
        // this covers a WooCommerce removed behind WordPress's back.
        if (class_exists('WooCommerce')) {
            $plugin->boot();
        }
    });
})();
