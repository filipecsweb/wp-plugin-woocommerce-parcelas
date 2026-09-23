<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Tests\Unit\Lifecycle;

use Brain\Monkey\Functions;
use InstallmentPricesForWooCommerce\Lifecycle\Uninstaller;

it('removes the settings and product meta, 1.x’s included', function (): void {
    $deleted = [];
    Functions\when('delete_option')->alias(function (string $name) use (&$deleted): bool {
        $deleted[] = "option:$name";
        return true;
    });
    Functions\when('delete_post_meta_by_key')->alias(function (string $key) use (&$deleted): bool {
        $deleted[] = "meta:$key";
        return true;
    });

    Uninstaller::uninstall('installment_prices_for_woocommerce');

    expect($deleted)->toEqualCanonicalizing([
        'option:installment_prices_for_woocommerce_settings',
        'option:fswp_settings',
        'meta:_installment_prices_for_woocommerce',
        'meta:fswp_post_meta',
    ]);
});
