<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Lifecycle;

use InstallmentPricesForWooCommerce\Settings\StorageKeys;

/**
 * Removes everything the plugin stores, 1.x's data included.
 *
 * @since 2.0.0
 */
final class Uninstaller
{
    /**
     * @since 2.0.0
     */
    public static function uninstall(string $optionPrefix): void
    {
        delete_option(StorageKeys::settings($optionPrefix));
        delete_option(StorageKeys::LEGACY_SETTINGS);
        delete_post_meta_by_key(StorageKeys::productMeta($optionPrefix));
        delete_post_meta_by_key(StorageKeys::LEGACY_PRODUCT_META);
    }
}
