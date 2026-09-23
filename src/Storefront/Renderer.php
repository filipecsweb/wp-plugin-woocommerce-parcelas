<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Storefront;

use InstallmentPricesForWooCommerce\Pricing\Calculator;
use InstallmentPricesForWooCommerce\Pricing\DisplayPrice;
use InstallmentPricesForWooCommerce\Product\Overrides;
use InstallmentPricesForWooCommerce\Settings\Settings;
use WC_Product;

/**
 * Prints a product's installment price and cash price.
 *
 * @since 2.0.0
 */
final class Renderer
{
    /**
     * @since 2.0.0
     */
    public function __construct(
        private readonly Settings $settings,
        private readonly Overrides $overrides,
    ) {
    }

    /**
     * @since 2.0.0
     */
    public function loop(): void
    {
        $this->print('loop');
    }

    /**
     * @since 2.0.0
     */
    public function single(): void
    {
        $this->print('single');
    }

    /**
     * Adds the chosen variation's lines under its price. WooCommerce shows that price
     * only when variations cost different amounts; otherwise the product's own lines
     * already hold for every variation.
     *
     * @since 2.0.0
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function variation(array $data, WC_Product $product, WC_Product $variation): array
    {
        if (is_string($data['price_html'] ?? null) && $data['price_html'] !== '') {
            $data['price_html'] .= wp_kses($this->html($variation, 'single', $product), self::allowedHtml());
        }

        return $data;
    }

    /**
     * $owner is the product whose own settings apply: the parent, for a variation.
     * Each line's HTML passes through a filter, with the product and the context
     * ('loop' in product lists, 'single' on the product page):
     * installment_prices_for_woocommerce_installments_html and
     * installment_prices_for_woocommerce_cash_html.
     *
     * @since 2.0.0
     */
    public function html(WC_Product $product, string $context, ?WC_Product $owner = null): string
    {
        $price = DisplayPrice::of($product);

        if ($price === null) {
            return '';
        }

        $settings  = $this->settings->all();
        $overrides = $this->overrides->for($owner ?? $product);
        $inStock   = $product->is_in_stock();
        $lines     = '';

        $installments = $settings['installments'];

        if ($installments['enabled'] && ! $overrides['installments_disabled'] && ($inStock || $installments['out_of_stock'])) {
            $plan = Calculator::installments($price['price'], $overrides['max'] ?? $installments['max'], $installments['min_amount'], wc_get_price_decimals());

            if ($plan !== null) {
                $count = sprintf(
                    /* translators: %d: number of installments; the amount of each follows. */
                    _n('%d installment of', '%d installments of', $plan['count'], 'woocommerce-parcelas'),
                    $plan['count']
                );
                // A price range starts from its cheapest option, which "From" says instead of the store's prefix.
                $prefix = $price['ranged'] ? __('From', 'woocommerce-parcelas') : $installments['prefix'];
                $line   = $this->line('installments', trim($prefix . ' ' . $count), $plan['amount'], $installments['suffix']);
                $lines .= self::htmlOr($line, apply_filters('installment_prices_for_woocommerce_installments_html', $line, $product, $context));
            }
        }

        $cash = $settings['cash'];

        if ($cash['enabled'] && ! $overrides['cash_disabled'] && ($inStock || $cash['out_of_stock'])) {
            $amount = Calculator::cash($price['price'], $overrides['discount'] ?? $cash['discount'], $overrides['discount_type'] ?? $cash['discount_type']);

            if ($amount !== null) {
                $line   = $this->line('cash', $cash['prefix'], $amount, $cash['suffix']);
                $lines .= self::htmlOr($line, apply_filters('installment_prices_for_woocommerce_cash_html', $line, $product, $context));
            }
        }

        return $lines === '' ? '' : sprintf('<div class="installment-prices installment-prices--%s">%s</div>', esc_attr($context), $lines);
    }

    /**
     * WHY the global: WooCommerce points it at the product each list item and
     * product page renders, and its own hooked functions read it too.
     *
     * @since 2.0.0
     */
    private function print(string $context): void
    {
        global $product;

        if ($product instanceof WC_Product) {
            echo wp_kses($this->html($product, $context), self::allowedHtml());
        }
    }

    /**
     * @since 2.0.0
     */
    private function line(string $kind, string $prefix, float $amount, string $suffix): string
    {
        $parts = [];

        if ($prefix !== '') {
            $parts[] = sprintf('<span class="installment-prices__prefix">%s</span>', esc_html($prefix));
        }

        $parts[] = sprintf('<span class="installment-prices__amount">%s</span>', wc_price($amount));

        if ($suffix !== '') {
            $parts[] = sprintf('<span class="installment-prices__suffix">%s</span>', esc_html($suffix));
        }

        return sprintf('<p class="price installment-prices__%s">%s</p>', esc_attr($kind), implode(' ', $parts));
    }

    /**
     * A filter's result, unless a callback returned something other than HTML.
     *
     * @since 2.0.0
     */
    private static function htmlOr(string $html, mixed $filtered): string
    {
        return is_string($filtered) ? $filtered : $html;
    }

    /**
     * Post HTML plus what wc_price() adds to it and wp_kses_post() would strip: the
     * <bdi> around the amount and the currency symbol's translate attribute.
     *
     * @since 2.0.0
     *
     * @return array<array<mixed>>
     */
    private static function allowedHtml(): array
    {
        $allowed         = array_filter(wp_kses_allowed_html('post'), 'is_array');
        $allowed['bdi']  = [];
        $allowed['span'] = ['translate' => true] + ($allowed['span'] ?? []);

        return $allowed;
    }
}
