<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Pricing;

use WC_Product;
use WC_Product_Grouped;
use WC_Product_Variable;

/**
 * The price a product's installments and cash price start from: the lowest price a
 * shopper sees, taxed the way the store displays prices, as WooCommerce's own price
 * HTML computes it.
 *
 * @since 2.0.0
 */
final class DisplayPrice
{
    /**
     * $ranged says other prices differ (a variable or grouped product), so the
     * figures are "from" figures. Null when the product has no price.
     *
     * @since 2.0.0
     *
     * @return array{price: float, ranged: bool}|null
     */
    public static function of(WC_Product $product): ?array
    {
        if ($product instanceof WC_Product_Variable) {
            $min = (float) $product->get_variation_price('min', true);
            $max = (float) $product->get_variation_price('max', true);
        } elseif ($product instanceof WC_Product_Grouped) {
            $prices = [];

            foreach ($product->get_children() as $childId) {
                $child = wc_get_product($childId);

                if ($child instanceof WC_Product && wc_products_array_filter_visible_grouped($child) && $child->get_price() !== '') {
                    $prices[] = (float) wc_get_price_to_display($child);
                }
            }

            $min = $prices === [] ? 0.0 : min($prices);
            $max = $prices === [] ? 0.0 : max($prices);
        } else {
            $min = (float) wc_get_price_to_display($product);
            $max = $min;
        }

        return $min > 0 ? ['price' => $min, 'ranged' => $min !== $max] : null;
    }
}
