<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Lifecycle;

use InstallmentPricesForWooCommerce\Settings\StorageKeys;

/**
 * Removes everything the plugin stores, 1.x's data included, on every site of a
 * network.
 *
 * @since 2.0.0
 */
final class Uninstaller
{
    /**
     * WHY every site: WordPress runs uninstall once, in the main site's context, even
     * for a plugin active across a network.
     *
     * @since 2.0.0
     */
    public static function uninstall(string $optionPrefix): void
    {
        if (! is_multisite()) {
            self::purge($optionPrefix);

            return;
        }

        foreach (get_sites(['fields' => 'ids', 'number' => 0]) as $siteId) {
            switch_to_blog((int) $siteId);
            self::purge($optionPrefix);
            restore_current_blog();
        }
    }

    /**
     * @since 2.0.0
     */
    private static function purge(string $optionPrefix): void
    {
        delete_option(StorageKeys::settings($optionPrefix));
        delete_option(StorageKeys::LEGACY_SETTINGS);
        delete_post_meta_by_key(StorageKeys::productMeta($optionPrefix));
        delete_post_meta_by_key(StorageKeys::LEGACY_PRODUCT_META);
    }
}
