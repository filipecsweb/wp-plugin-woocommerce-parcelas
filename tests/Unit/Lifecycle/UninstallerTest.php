<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Tests\Unit\Lifecycle;

use Brain\Monkey\Functions;
use InstallmentPricesForWooCommerce\Lifecycle\Uninstaller;

beforeEach(function (): void {
    $this->blog    = 1;
    $this->deleted = [];
    Functions\when('delete_option')->alias(function (string $name): bool {
        $this->deleted[] = "{$this->blog}:option:$name";
        return true;
    });
    Functions\when('delete_post_meta_by_key')->alias(function (string $key): bool {
        $this->deleted[] = "{$this->blog}:meta:$key";
        return true;
    });
});

$purged = fn (int $blog): array => [
    "$blog:option:installment_prices_for_woocommerce_settings",
    "$blog:option:fswp_settings",
    "$blog:meta:_installment_prices_for_woocommerce",
    "$blog:meta:fswp_post_meta",
];

it('removes the settings and product meta, 1.x’s included', function () use ($purged): void {
    Functions\when('is_multisite')->justReturn(false);

    Uninstaller::uninstall('installment_prices_for_woocommerce');

    expect($this->deleted)->toEqualCanonicalizing($purged(1));
});

it('removes them from every site of a network', function () use ($purged): void {
    Functions\when('is_multisite')->justReturn(true);
    Functions\when('get_sites')->justReturn([1, 2]);
    Functions\when('switch_to_blog')->alias(function (int $blog): bool {
        $this->blog = $blog;
        return true;
    });
    Functions\when('restore_current_blog')->alias(function (): bool {
        $this->blog = 1;
        return true;
    });

    Uninstaller::uninstall('installment_prices_for_woocommerce');

    expect($this->deleted)->toEqualCanonicalizing([...$purged(1), ...$purged(2)]);
});
