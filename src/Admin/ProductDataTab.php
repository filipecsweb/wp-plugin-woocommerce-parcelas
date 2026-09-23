<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Admin;

use InstallmentPricesForWooCommerce\Foundation\Hooks\Action;
use InstallmentPricesForWooCommerce\Foundation\Hooks\Filter;
use InstallmentPricesForWooCommerce\Product\Overrides;
use InstallmentPricesForWooCommerce\Settings\Settings;
use WC_Product;

/**
 * An "Installments" tab in the product editor's Product data box, for the product's
 * own exceptions to the store settings. Built from WooCommerce's own field helpers,
 * so it looks and behaves like the tabs around it.
 *
 * @since 2.0.0
 */
final class ProductDataTab
{
    /**
     * CONTRACT: the panel's element id; WooCommerce shows it when its tab is clicked.
     *
     * @since 2.0.0
     */
    public const PANEL_ID = 'installment_prices_product_data';

    /**
     * Posted field names, keyed by the Overrides field each one sets.
     *
     * @since 2.0.0
     */
    private const FIELDS = [
        'installments_disabled' => 'installment_prices_installments_disabled',
        'max'                   => 'installment_prices_max',
        'cash_disabled'         => 'installment_prices_cash_disabled',
        'discount'              => 'installment_prices_discount',
        'discount_type'         => 'installment_prices_discount_type',
    ];

    /**
     * @since 2.0.0
     */
    private const NONCE_ACTION = 'installment_prices_save_product';

    /**
     * @since 2.0.0
     */
    private const NONCE_FIELD = 'installment_prices_nonce';

    /**
     * @since 2.0.0
     */
    public function __construct(
        private readonly Overrides $overrides,
        private readonly Settings $settings,
    ) {
    }

    /**
     * @since 2.0.0
     *
     * @param array<string, array<string, mixed>> $tabs
     *
     * @return array<string, array<string, mixed>>
     */
    #[Filter('woocommerce_product_data_tabs')]
    public function addTab(array $tabs): array
    {
        $tabs['installment_prices'] = [
            'label'    => __('Installments', 'woocommerce-parcelas'),
            'target'   => self::PANEL_ID,
            'class'    => [],
            'priority' => 75,
        ];

        return $tabs;
    }

    /**
     * @since 2.0.0
     */
    #[Action('woocommerce_product_data_panels')]
    public function renderPanel(): void
    {
        global $product_object;

        $product = $product_object instanceof WC_Product ? $product_object : wc_get_product(get_the_ID());

        if (! $product instanceof WC_Product) {
            return;
        }

        $own    = $this->overrides->for($product);
        $store  = $this->settings->all();
        $inherit = __('Leave blank to use the store setting.', 'woocommerce-parcelas');

        echo '<div id="' . esc_attr(self::PANEL_ID) . '" class="panel woocommerce_options_panel hidden">';
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD);

        echo '<div class="options_group">';
        woocommerce_wp_checkbox([
            'id'          => self::FIELDS['installments_disabled'],
            'label'       => __('Hide installments', 'woocommerce-parcelas'),
            'description' => __('Show no installment price for this product.', 'woocommerce-parcelas'),
            'value'       => $own['installments_disabled'] ? 'yes' : 'no',
        ]);
        woocommerce_wp_text_input([
            'id'                => self::FIELDS['max'],
            'label'             => __('Maximum installments', 'woocommerce-parcelas'),
            'type'              => 'number',
            'value'             => $own['max'] === null ? '' : (string) $own['max'],
            'placeholder'       => (string) $store['installments']['max'],
            'custom_attributes' => ['min' => (string) Settings::MIN_INSTALLMENTS, 'step' => '1'],
            'desc_tip'          => true,
            'description'       => $inherit,
        ]);
        echo '</div>';

        echo '<div class="options_group">';
        woocommerce_wp_checkbox([
            'id'          => self::FIELDS['cash_disabled'],
            'label'       => __('Hide cash price', 'woocommerce-parcelas'),
            'description' => __('Show no cash price for this product.', 'woocommerce-parcelas'),
            'value'       => $own['cash_disabled'] ? 'yes' : 'no',
        ]);
        woocommerce_wp_text_input([
            'id'                => self::FIELDS['discount'],
            'label'             => __('Cash discount', 'woocommerce-parcelas'),
            'type'              => 'number',
            'value'             => $own['discount'] === null ? '' : (string) $own['discount'],
            'placeholder'       => (string) $store['cash']['discount'],
            'custom_attributes' => ['min' => '0', 'step' => 'any'],
            'desc_tip'          => true,
            'description'       => $inherit,
        ]);
        woocommerce_wp_select([
            'id'      => self::FIELDS['discount_type'],
            'label'   => __('Discount type', 'woocommerce-parcelas'),
            'value'   => $own['discount_type'] ?? '',
            'options' => ['' => __('Store setting', 'woocommerce-parcelas')] + array_column(Settings::choices()['discountTypes'], 'label', 'value'),
        ]);
        echo '</div>';

        echo '</div>';
    }

    /**
     * @since 2.0.0
     */
    #[Action('woocommerce_admin_process_product_object')]
    public function save(WC_Product $product): void
    {
        $nonce = isset($_POST[self::NONCE_FIELD]) && is_string($_POST[self::NONCE_FIELD]) ? sanitize_text_field(wp_unslash($_POST[self::NONCE_FIELD])) : '';

        if (! wp_verify_nonce($nonce, self::NONCE_ACTION)) {
            return;
        }

        $input = [];

        foreach (self::FIELDS as $key => $name) {
            $input[$key] = isset($_POST[$name]) && is_string($_POST[$name]) ? sanitize_text_field(wp_unslash($_POST[$name])) : '';
        }

        // An unticked checkbox isn't posted at all.
        $input['installments_disabled'] = $input['installments_disabled'] !== '';
        $input['cash_disabled']         = $input['cash_disabled'] !== '';

        $this->overrides->save($product, $input);
    }
}
