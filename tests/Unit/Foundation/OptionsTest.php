<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Tests\Unit\Foundation;

use Brain\Monkey\Functions;
use InstallmentPricesForWooCommerce\Foundation\Settings\Options;

beforeEach(function (): void {
    $this->store = [];
    Functions\when('get_option')->alias(fn ($key, $default = false) => $this->store[$key] ?? $default);
    Functions\when('update_option')->alias(function ($key, $value) {
        $this->store[$key] = $value;
        return true;
    });
    Functions\when('delete_option')->alias(function ($key) {
        unset($this->store[$key]);
        return true;
    });
});

it('reads defaults when nothing is stored', function (): void {
    $options = new Options('opt', ['debounce' => 5]);

    expect($options->getInt('debounce'))->toBe(5)
        ->and($options->getString('missing', 'fallback'))->toBe('fallback');
});

it('persists and re-reads typed values', function (): void {
    $options = new Options('opt');
    $options->set('flag', true);
    $options->set('count', 12);

    $fresh = new Options('opt');

    expect($fresh->getBool('flag'))->toBeTrue()
        ->and($fresh->getInt('count'))->toBe(12);
});

it('returns the provided default for a stored null bool', function (): void {
    $options = new Options('opt');
    $options->set('flag', null);

    expect($options->getBool('flag', true))->toBeTrue();
});

it('forgets a single key without dropping the row', function (): void {
    $options = new Options('opt');
    $options->fill(['a' => 1, 'b' => 2]);
    $options->forget('a');

    expect($options->has('a'))->toBeFalse()
        ->and($options->getInt('b'))->toBe(2);
});
