<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Tests\Unit\Storefront;

use Brain\Monkey\Functions;
use InstallmentPricesForWooCommerce\Foundation\Settings\Options;
use InstallmentPricesForWooCommerce\Settings\Settings;
use InstallmentPricesForWooCommerce\Storefront\Styles;

beforeEach(function (): void {
    $this->store = [];
    Functions\when('get_option')->alias(fn ($key, $default = false) => $this->store[$key] ?? $default);
    Functions\when('sanitize_text_field')->alias(fn (string $value): string => $value);
    Functions\when('sanitize_hex_color')->alias(fn (string $color): string => $color);

    $this->css = function (array $settings): string {
        $this->store['ipfw_settings'] = Settings::sanitize($settings);

        return (new Styles(new Settings(new Options('ipfw_settings')), 'ipfw', '2.0.0'))->css();
    };
});

it('prints nothing when nothing is styled', function (): void {
    expect(($this->css)([]))->toBe('');
});

it('writes one rule per styled part, and reaches the amount WooCommerce marks up', function (): void {
    $css = ($this->css)([
        'placement' => ['single' => ['align' => 'center']],
        'style'     => ['cash' => ['loop' => [
            'prefix' => ['color' => '#cc1818', 'size' => '18px'],
            'amount' => ['weight' => '700'],
        ]]],
    ]);

    expect(explode("\n", $css))->toBe([
        '.installment-prices--loop .installment-prices__cash .installment-prices__prefix{color:#cc1818!important;font-size:18px!important}',
        '.installment-prices--loop .installment-prices__cash .installment-prices__amount,.installment-prices--loop .installment-prices__cash .installment-prices__amount .amount{font-weight:700!important}',
        '.installment-prices--single .price{text-align:center!important}',
    ]);
});
