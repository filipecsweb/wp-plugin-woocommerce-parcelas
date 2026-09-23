<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Product;

use InstallmentPricesForWooCommerce\Settings\LegacySettings;
use InstallmentPricesForWooCommerce\Settings\Settings;
use InstallmentPricesForWooCommerce\Settings\StorageKeys;
use WC_Product;

/**
 * A product's own exceptions to the store settings, kept as one meta array. A null
 * field means "use the store setting".
 *
 * @since 2.0.0
 *
 * @phpstan-type Values array{installments_disabled: bool, max: int|null, cash_disabled: bool, discount: float|null, discount_type: string|null}
 */
final class Overrides
{
    /**
     * @since 2.0.0
     */
    public function __construct(private readonly string $metaKey)
    {
    }

    /**
     * WHY get_post_meta and not WC_Product::get_meta(): product queries prime the
     * post meta cache for every product in a list, while get_meta() would read each
     * product's meta with a query of its own.
     *
     * @since 2.0.0
     *
     * @return Values
     */
    public function for(WC_Product $product): array
    {
        $stored = get_post_meta($product->get_id(), $this->metaKey, true);

        if (! is_array($stored)) {
            $legacy = get_post_meta($product->get_id(), StorageKeys::LEGACY_PRODUCT_META, true);
            $stored = is_array($legacy) ? LegacySettings::toOverrides($legacy) : [];
        }

        return self::sanitize($stored);
    }

    /**
     * CONTRACT: WooCommerce persists the meta when it saves $product.
     *
     * @since 2.0.0
     *
     * @param array<array-key, mixed> $input
     */
    public function save(WC_Product $product, array $input): void
    {
        $product->update_meta_data($this->metaKey, self::sanitize($input));
    }

    /**
     * @since 2.0.0
     *
     * @return Values
     */
    public static function sanitize(mixed $input): array
    {
        $in       = is_array($input) ? $input : [];
        $max      = $in['max'] ?? null;
        $discount = $in['discount'] ?? null;
        $type     = $in['discount_type'] ?? null;

        return [
            'installments_disabled' => filter_var($in['installments_disabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'max'                   => self::blank($max) ? null : max(Settings::MIN_INSTALLMENTS, is_numeric($max) ? (int) $max : 0),
            'cash_disabled'         => filter_var($in['cash_disabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'discount'              => self::blank($discount) ? null : Settings::amount($discount),
            'discount_type'         => in_array($type, Settings::DISCOUNT_TYPES, true) ? $type : null,
        ];
    }

    /**
     * @since 2.0.0
     */
    private static function blank(mixed $value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }
}
