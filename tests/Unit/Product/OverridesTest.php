<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Tests\Unit\Product;

use Brain\Monkey\Functions;
use InstallmentPricesForWooCommerce\Product\Overrides;
use InstallmentPricesForWooCommerce\Settings\Settings;
use WC_Product;

it('treats a blank field as the store setting', function (): void {
    expect(Overrides::sanitize(['max' => '', 'discount' => ' ', 'discount_type' => '']))
        ->toBe(['installments_disabled' => false, 'max' => null, 'cash_disabled' => false, 'discount' => null, 'discount_type' => null]);
});

it('coerces the posted fields', function (): void {
    expect(Overrides::sanitize(['installments_disabled' => true, 'max' => '1', 'cash_disabled' => '1', 'discount' => '7,5', 'discount_type' => 'fixed']))
        ->toBe(['installments_disabled' => true, 'max' => 2, 'cash_disabled' => true, 'discount' => 7.5, 'discount_type' => 'fixed']);
});

it('caps the maximum installments', function (): void {
    expect(Overrides::sanitize(['max' => '100000000'])['max'])->toBe(Settings::MAX_INSTALLMENTS);
});

it('reads a maximum that is not a number as the store setting', function (): void {
    expect(Overrides::sanitize(['max' => 'twelve'])['max'])->toBeNull();
});

it('reads a discount that is not a number as the store setting', function (): void {
    expect(Overrides::sanitize(['discount' => '5%'])['discount'])->toBeNull();
});

it('falls back to a product’s 1.x overrides until it is saved again', function (): void {
    Functions\when('get_post_meta')->alias(fn (int $id, string $key): mixed => $key === 'fswp_post_meta'
        ? ['disable_installments' => '0', 'installment_qty' => '4', 'disable_in_cash' => '1', 'in_cash_discount' => '', 'in_cash_discount_type' => null]
        : '');

    expect((new Overrides('_ipfw'))->for(new WC_Product(7)))
        ->toBe(['installments_disabled' => false, 'max' => 4, 'cash_disabled' => true, 'discount' => null, 'discount_type' => null]);
});
