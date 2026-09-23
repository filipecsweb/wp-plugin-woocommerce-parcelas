<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Tests\Unit\Settings;

use Brain\Monkey\Functions;
use InstallmentPricesForWooCommerce\Foundation\Settings\Options;
use InstallmentPricesForWooCommerce\Settings\Settings;

beforeEach(function (): void {
    $this->store = [];
    Functions\when('get_option')->alias(fn ($key, $default = false) => $this->store[$key] ?? $default);
    Functions\when('update_option')->alias(function ($key, $value) {
        $this->store[$key] = $value;
        return true;
    });
    Functions\when('sanitize_text_field')->alias(fn (string $value): string => trim(strip_tags($value)));
    Functions\when('sanitize_hex_color')->alias(
        fn (string $color): ?string => $color === '' || preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $color) ? $color : null
    );
    Functions\when('__')->returnArg(1);

    $this->make = fn (): Settings => new Settings(new Options('ipfw_settings'));
});

it('defaults to both lines off, placed right after the price', function (): void {
    $defaults = Settings::defaults();

    expect($defaults['installments'])->toBe(['enabled' => false, 'prefix' => '', 'max' => 2, 'suffix' => '', 'min_amount' => 0.0, 'out_of_stock' => false])
        ->and($defaults['cash']['discount_type'])->toBe('percent')
        ->and($defaults['placement']['loop'])->toBe(['hook' => 'woocommerce_after_shop_loop_item_title', 'priority' => 15, 'align' => ''])
        ->and($defaults['placement']['single'])->toBe(['hook' => 'woocommerce_single_product_summary', 'priority' => 15, 'align' => ''])
        ->and($defaults['style']['cash']['single']['amount'])->toBe(['color' => '', 'weight' => '', 'size' => '']);
});

it('coerces what a form posts', function (): void {
    $settings = Settings::sanitize([
        'installments' => ['enabled' => 'true', 'prefix' => ' <b>Up to</b> ', 'max' => '12', 'min_amount' => '5,95'],
        'cash'         => ['enabled' => '1', 'discount' => '4.5', 'discount_type' => 'fixed'],
        'placement'    => ['single' => ['hook' => 'woocommerce_after_add_to_cart_button', 'priority' => '30', 'align' => 'center']],
    ]);

    expect($settings['installments'])->toMatchArray(['enabled' => true, 'prefix' => 'Up to', 'max' => 12, 'min_amount' => 5.95])
        ->and($settings['cash'])->toMatchArray(['enabled' => true, 'discount' => 4.5, 'discount_type' => 'fixed'])
        ->and($settings['placement']['single'])->toBe(['hook' => 'woocommerce_after_add_to_cart_button', 'priority' => 30, 'align' => 'center']);
});

it('keeps values inside their limits', function (): void {
    $settings = Settings::sanitize([
        'installments' => ['max' => '1', 'min_amount' => '-3'],
        'cash'         => ['discount' => '150', 'discount_type' => 'bogus'],
        'placement'    => ['loop' => ['hook' => 'wp_footer', 'align' => 'justify']],
    ]);

    expect($settings['installments']['max'])->toBe(2)
        ->and($settings['installments']['min_amount'])->toBe(0.0)
        ->and($settings['cash']['discount_type'])->toBe('percent')
        ->and($settings['cash']['discount'])->toBe(100.0)
        ->and($settings['placement']['loop']['hook'])->toBe('woocommerce_after_shop_loop_item_title')
        ->and($settings['placement']['loop']['align'])->toBe('');
});

it('admits only CSS-safe style values', function (): void {
    $style = Settings::sanitize(['style' => ['installments' => ['loop' => [
        'prefix' => ['color' => '#CC1818', 'weight' => '700', 'size' => '1.2EM'],
        'amount' => ['color' => 'red;}body{display:none', 'weight' => 'inherit', 'size' => '18'],
        'suffix' => ['color' => '#fff', 'weight' => '450', 'size' => '12px;color:red'],
    ]]]])['style']['installments']['loop'];

    expect($style['prefix'])->toBe(['color' => '#CC1818', 'weight' => '700', 'size' => '1.2em'])
        ->and($style['amount'])->toBe(['color' => '', 'weight' => '', 'size' => ''])
        ->and($style['suffix'])->toBe(['color' => '#fff', 'weight' => '', 'size' => '']);
});

it('survives input of the wrong shape', function (): void {
    expect(Settings::sanitize('garbage'))->toBe(Settings::defaults())
        ->and(Settings::sanitize(['installments' => 'x', 'style' => ['cash' => 5]]))->toBe(Settings::defaults());
});

it('merges a partial save over the stored settings', function (): void {
    ($this->make)()->save(['installments' => ['enabled' => true, 'max' => 6]]);
    $saved = ($this->make)()->save(['cash' => ['enabled' => true]]);

    expect($saved['installments'])->toMatchArray(['enabled' => true, 'max' => 6])
        ->and($saved['cash']['enabled'])->toBeTrue()
        ->and(($this->make)()->all())->toBe($saved);
});

it('stores the defaults on a fresh install', function (): void {
    ($this->make)()->install();

    expect($this->store['ipfw_settings'])->toBe(Settings::defaults());
});

it('carries 1.x settings over on the first run', function (): void {
    $this->store['fswp_settings'] = ['enable_installments' => '1', 'installment_qty' => '6', 'installment_prefix' => 'Em até'];

    ($this->make)()->install();

    expect($this->store['ipfw_settings']['installments'])->toMatchArray(['enabled' => true, 'max' => 6, 'prefix' => 'Em até']);
});

it('never re-imports once its own settings exist', function (): void {
    $this->store['ipfw_settings'] = Settings::sanitize(['installments' => ['max' => 4]]);
    $this->store['fswp_settings'] = ['installment_qty' => '6'];

    ($this->make)()->install();

    expect($this->store['ipfw_settings']['installments']['max'])->toBe(4);
});

it('labels exactly the values it accepts, in order', function (): void {
    $values = fn (string $list): array => array_column(Settings::choices()[$list], 'value');

    expect($values('loopHooks'))->toBe(Settings::LOOP_HOOKS)
        ->and($values('singleHooks'))->toBe(Settings::SINGLE_HOOKS)
        ->and($values('alignments'))->toBe(Settings::ALIGNMENTS)
        ->and($values('weights'))->toBe(Settings::WEIGHTS)
        ->and($values('discountTypes'))->toBe(Settings::DISCOUNT_TYPES);
});
