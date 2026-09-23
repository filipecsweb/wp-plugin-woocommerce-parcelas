<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Tests\Unit\Pricing;

use InstallmentPricesForWooCommerce\Pricing\Calculator;

it('splits the price into the maximum number of installments', function (): void {
    expect(Calculator::installments(100.0, 10, 0.0, 2))->toBe(['count' => 10, 'amount' => 10.0]);
});

it('offers fewer installments to keep each at the minimum', function (): void {
    expect(Calculator::installments(100.0, 10, 30.0, 2))->toBe(['count' => 3, 'amount' => 100 / 3])
        ->and(Calculator::installments(40.0, 10, 5.0, 2))->toBe(['count' => 8, 'amount' => 5.0]);
});

it('compares the installment as the store shows it, rounded to its decimals', function (): void {
    // 29.97 / 3 is 9.9899999… in floating point; shown as 9.99, it meets a 9.99 minimum.
    expect(Calculator::installments(29.97, 3, 9.99, 2))->toMatchArray(['count' => 3]);
});

it('offers nothing when even two installments fall below the minimum', function (): void {
    expect(Calculator::installments(9.0, 10, 5.0, 2))->toBeNull()
        ->and(Calculator::installments(5.0, 10, 5.0, 2))->toBeNull();
});

it('never goes below two installments, whatever the maximum', function (): void {
    expect(Calculator::installments(100.0, 1, 0.0, 2))->toBe(['count' => 2, 'amount' => 50.0]);
});

it('prices nothing free', function (): void {
    expect(Calculator::installments(0.0, 10, 0.0, 2))->toBeNull()
        ->and(Calculator::cash(0.0, 10.0, 'percent'))->toBeNull();
});

it('takes a percentage or a fixed amount off for the cash price', function (): void {
    expect(Calculator::cash(100.0, 10.0, 'percent'))->toBe(90.0)
        ->and(Calculator::cash(100.0, 15.5, 'fixed'))->toBe(84.5)
        ->and(Calculator::cash(100.0, 0.0, 'percent'))->toBe(100.0);
});

it('shows no cash price when the discount leaves nothing to pay', function (): void {
    expect(Calculator::cash(100.0, 100.0, 'fixed'))->toBeNull()
        ->and(Calculator::cash(100.0, 150.0, 'percent'))->toBeNull();
});
