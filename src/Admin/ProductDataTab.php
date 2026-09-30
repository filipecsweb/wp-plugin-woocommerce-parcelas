<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Admin;

use InstallmentPricesForWooCommerce\Foundation\Hooks\Action;
use InstallmentPricesForWooCommerce\Foundation\Hooks\Filter;
use InstallmentPricesForWooCommerce\Identity;
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
    public const PANEL_ID = Identity::SLUG . '-product-data';

    /**
     * Posted field names, keyed by the Overrides field each one sets.
     *
     * @since 2.0.0
     */
    private const FIELDS = [
        'installments_disabled' => Identity::SLUG . '-installments-disabled',
        'max'                   => Identity::SLUG . '-max',
        'cash_disabled'         => Identity::SLUG . '-cash-disabled',
        'discount'              => Identity::SLUG . '-discount',
        'discount_type'         => Identity::SLUG . '-discount-type',
    ];

    /**
     * @since 2.0.0
     */
    private const NONCE_ACTION = Identity::SLUG . '-save-product';

    /**
     * @since 2.0.0
     */
    private const NONCE_FIELD = Identity::SLUG . '-nonce';

    /**
     * WHY 75: right after WooCommerce's last tab, Advanced (70).
     *
     * @since 2.0.0
     */
    private const TAB_PRIORITY = 75;

    /**
     * WHY text fields, not number fields: the panel is hidden while another tab is
     * open, and the browser then blocks the product's save, without a word, over a
     * value a number field rejects, even a half-typed one like "1e".
     * Overrides::sanitize() reads whatever was typed.
     *
     * @since 2.0.0
     */
    private const NUMBER_ATTRIBUTES = ['inputmode' => 'decimal'];

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
        $tabs[Identity::SLUG] = [
            'label'    => __('Installments', 'woocommerce-parcelas'),
            'target'   => self::PANEL_ID,
            'class'    => [],
            'priority' => self::TAB_PRIORITY,
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

        $own   = $this->overrides->for($product);
        $store = $this->settings->all();

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
            'value'             => $own['max'] === null ? '' : (string) $own['max'],
            'placeholder'       => (string) $store['installments']['max'],
            'custom_attributes' => self::NUMBER_ATTRIBUTES,
            'desc_tip'          => true,
            'description'       => sprintf(
                /* translators: 1: fewest installments allowed; 2: most installments allowed. */
                __('From %1$d to %2$d. Leave blank to use the store setting.', 'woocommerce-parcelas'),
                Settings::MIN_INSTALLMENTS,
                Settings::MAX_INSTALLMENTS
            ),
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
            'value'             => $own['discount'] === null ? '' : (string) $own['discount'],
            'placeholder'       => (string) $store['cash']['discount'],
            'custom_attributes' => self::NUMBER_ATTRIBUTES,
            'desc_tip'          => true,
            'description'       => __('Leave blank to use the store setting.', 'woocommerce-parcelas'),
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
