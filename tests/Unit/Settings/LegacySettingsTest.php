<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Tests\Unit\Settings;

use Brain\Monkey\Functions;
use InstallmentPricesForWooCommerce\Settings\LegacySettings;
use InstallmentPricesForWooCommerce\Settings\Settings;

beforeEach(function (): void {
    Functions\when('sanitize_text_field')->alias(fn (string $value): string => trim(strip_tags($value)));
    Functions\when('sanitize_hex_color')->alias(
        fn (string $color): ?string => $color === '' || preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $color) ? $color : null
    );
});

// A 1.3.5 settings row as its form saved it.
const ONE_THREE_FIVE = [
    'enable_installments'                 => '1',
    'installment_prefix'                  => 'Em até',
    'installment_qty'                     => '12',
    'installment_suffix'                  => 'sem juros',
    'installment_minimum_value'           => '5,95',
    'enable_installments_if_out_of_stock' => 'yes',
    'enable_in_cash'                      => '1',
    'in_cash_prefix'                      => 'ou',
    'in_cash_discount'                    => '4,5',
    'in_cash_discount_type'               => '1',
    'in_cash_suffix'                      => 'no boleto',
    'enable_in_cash_if_out_of_stock'      => 'no',
    'fswp_in_loop_position'               => 'woocommerce_after_shop_loop_item',
    'fswp_in_loop_position_level'         => '20',
    'fswp_in_single_position'             => 'woocommerce_before_add_to_cart_button',
    'fswp_in_single_position_level'       => '5',
    'in_loop_alignment'                   => 'center',
    'in_single_alignment'                 => 'right',
    'installments_in_loop_prefix_color'   => '#dd3333',
    'installments_in_loop_amount_font-weight' => '700',
    'in_cash_in_single_suffix_font-size'  => '1.2em',
    'in_cash_in_single_prefix_font-weight' => 'inherit',
];

it('maps every 1.x setting to its 2.x place', function (): void {
    $settings = Settings::sanitize(LegacySettings::toSettings(ONE_THREE_FIVE));

    expect($settings['installments'])->toBe(['enabled' => true, 'prefix' => 'Em até', 'max' => 12, 'suffix' => 'sem juros', 'min_amount' => 5.95, 'out_of_stock' => true])
        ->and($settings['cash'])->toBe(['enabled' => true, 'prefix' => 'ou', 'discount' => 4.5, 'discount_type' => 'fixed', 'suffix' => 'no boleto', 'out_of_stock' => false])
        ->and($settings['placement'])->toBe([
            'loop'   => ['hook' => 'woocommerce_after_shop_loop_item', 'priority' => 20, 'align' => 'center'],
            'single' => ['hook' => 'woocommerce_before_add_to_cart_button', 'priority' => 5, 'align' => 'right'],
        ])
        ->and($settings['style']['installments']['loop']['prefix']['color'])->toBe('#dd3333')
        ->and($settings['style']['installments']['loop']['amount']['weight'])->toBe('700')
        ->and($settings['style']['cash']['single']['suffix']['size'])->toBe('1.2em')
        ->and($settings['style']['cash']['single']['prefix']['weight'])->toBe('');
});

it('reads a type left unset as a percentage, as 1.x did', function (): void {
    expect(Settings::sanitize(LegacySettings::toSettings(['in_cash_discount_type' => '']))['cash']['discount_type'])->toBe('percent');
});

it('maps a product’s 1.x overrides, blanks meaning the store setting', function (): void {
    expect(LegacySettings::toOverrides(['disable_installments' => '1', 'installment_qty' => '3', 'disable_in_cash' => '0', 'in_cash_discount' => '', 'in_cash_discount_type' => null]))
        ->toBe(['installments_disabled' => true, 'max' => '3', 'cash_disabled' => false, 'discount' => '', 'discount_type' => ''])
        ->and(LegacySettings::toOverrides(['in_cash_discount' => '10', 'in_cash_discount_type' => '0']))
        ->toMatchArray(['discount' => '10', 'discount_type' => 'percent']);
});
