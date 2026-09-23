<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Storefront;

use InstallmentPricesForWooCommerce\Settings\Settings;

/**
 * The alignment and text styles the settings give the price lines, as inline CSS.
 *
 * WHY !important: these are the store owner's explicit choices, and themes style
 * `.price` and `.amount` with selectors that would otherwise win.
 *
 * @since 2.0.0
 */
final class Styles
{
    /**
     * @since 2.0.0
     */
    private const PROPERTIES = ['color' => 'color', 'weight' => 'font-weight', 'size' => 'font-size'];

    /**
     * @since 2.0.0
     *
     * @param non-empty-string $handle
     */
    public function __construct(
        private readonly Settings $settings,
        private readonly string $handle,
        private readonly string $version,
    ) {
    }

    /**
     * @since 2.0.0
     */
    public function enqueue(): void
    {
        $css = $this->css();

        if ($css === '') {
            return;
        }

        wp_register_style($this->handle, false, [], $this->version);
        wp_enqueue_style($this->handle);
        wp_add_inline_style($this->handle, $css);
    }

    /**
     * CONTRACT: every value comes out of Settings::sanitize(), which admits only hex
     * colours, the listed weights and alignments, and plain CSS lengths.
     *
     * @since 2.0.0
     */
    public function css(): string
    {
        $settings = $this->settings->all();
        $rules    = [];

        foreach (Settings::CONTEXTS as $context) {
            $scope = '.installment-prices--' . $context;
            $align = $settings['placement'][$context]['align'];

            if ($align !== '') {
                $rules[] = sprintf('%s .price{text-align:%s!important}', $scope, $align);
            }

            foreach (Settings::KINDS as $kind) {
                foreach (Settings::PARTS as $part) {
                    $declarations = [];

                    foreach (self::PROPERTIES as $key => $property) {
                        $value = $settings['style'][$kind][$context][$part][$key];

                        if ($value !== '') {
                            $declarations[] = $property . ':' . $value . '!important';
                        }
                    }

                    if ($declarations === []) {
                        continue;
                    }

                    $selector = sprintf('%s .installment-prices__%s .installment-prices__%s', $scope, $kind, $part);

                    // Themes colour the .amount inside wc_price()'s markup directly.
                    if ($part === 'amount') {
                        $selector .= ',' . $selector . ' .amount';
                    }

                    $rules[] = $selector . '{' . implode(';', $declarations) . '}';
                }
            }
        }

        return implode("\n", $rules);
    }
}
