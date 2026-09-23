<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Tests\Unit\Admin;

use Brain\Monkey\Functions;
use InstallmentPricesForWooCommerce\Admin\SettingsPage;

beforeEach(function (): void {
    Functions\when('esc_html__')->returnArg(1);
    Functions\when('esc_url')->returnArg(1);
    Functions\when('add_query_arg')->alias(
        fn (string $key, string $value, string $url): string => $url . '?' . $key . '=' . $value
    );
    Functions\when('admin_url')->alias(fn (string $path): string => 'https://example.test/wp-admin/' . $path);
});

it('links the Plugins screen row to the page under the WooCommerce menu, ahead of the other actions', function (): void {
    $actions = (new SettingsPage())->pluginActionLinks(['deactivate' => '<a href="#">Deactivate</a>']);

    expect(array_keys($actions))->toBe(['settings', 'deactivate'])
        ->and($actions['settings'])->toContain('https://example.test/wp-admin/admin.php?page=installment-prices-for-woocommerce')
        ->and($actions['settings'])->toContain('>Settings</a>');
});
