<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Settings;

/**
 * Every database key this plugin reads or writes, built from the option prefix
 * (Plugin::optionPrefix()), so the write sites and the uninstaller can never
 * disagree.
 *
 * @since 2.0.0
 */
final class StorageKeys
{
    /**
     * @since 2.0.0
     */
    public const SETTINGS_SUFFIX = '_settings';

    /**
     * Version 1.x's settings row. Read once, to carry a store's setup over; deleted
     * only on uninstall, so rolling back to 1.x finds it intact.
     *
     * @since 2.0.0
     */
    public const LEGACY_SETTINGS = 'fswp_settings';

    /**
     * Version 1.x's per-product overrides, read until the product is saved again.
     *
     * @since 2.0.0
     */
    public const LEGACY_PRODUCT_META = 'fswp_post_meta';

    /**
     * @since 2.0.0
     */
    public static function settings(string $prefix): string
    {
        return $prefix . self::SETTINGS_SUFFIX;
    }

    /**
     * The leading underscore keeps the key out of the Custom Fields box.
     *
     * @since 2.0.0
     */
    public static function productMeta(string $prefix): string
    {
        return '_' . $prefix;
    }
}
