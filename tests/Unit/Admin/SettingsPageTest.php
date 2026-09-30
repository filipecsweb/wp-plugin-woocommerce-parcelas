<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Tests\Unit\Admin;

use Brain\Monkey\Functions;
use InstallmentPricesForWooCommerce\Admin\SettingsPage;
use InstallmentPricesForWooCommerce\Foundation\Assets\Vite;
use InstallmentPricesForWooCommerce\Foundation\I18n\TextDomain;
use InstallmentPricesForWooCommerce\Foundation\Plugin;
use InstallmentPricesForWooCommerce\Foundation\Settings\Options;
use InstallmentPricesForWooCommerce\Identity;
use InstallmentPricesForWooCommerce\Module\AdminUi\AdminAssets;
use InstallmentPricesForWooCommerce\Settings\Settings;

beforeEach(function (): void {
    Functions\when('esc_html__')->returnArg(1);
    Functions\when('esc_url')->returnArg(1);
    Functions\when('add_query_arg')->alias(
        fn (string $key, string $value, string $url): string => $url . '?' . $key . '=' . $value
    );
    Functions\when('admin_url')->alias(fn (string $path): string => 'https://example.test/wp-admin/' . $path);

    $this->page = new SettingsPage(
        new AdminAssets(new Vite('', '')),
        new TextDomain('woocommerce-parcelas', 'languages'),
        new Settings(new Options('settings')),
        Plugin::create(__FILE__, Identity::SLUG),
    );
});

it('links the Plugins screen row to the page under the WooCommerce menu, ahead of the other actions', function (): void {
    $actions = $this->page->pluginActionLinks(['deactivate' => '<a href="#">Deactivate</a>']);

    expect(array_keys($actions))->toBe(['settings', 'deactivate'])
        ->and($actions['settings'])->toContain('https://example.test/wp-admin/admin.php?page=installment-prices-for-woocommerce')
        ->and($actions['settings'])->toContain('>Settings</a>');
});

it('silences foreign notices on its own screen', function (): void {
    Functions\when('__')->returnArg(1);
    Functions\when('add_submenu_page')->justReturn('woocommerce_page_' . Identity::SLUG);
    $page = $this->page;

    $page->register();

    expect(has_action('load-woocommerce_page_' . Identity::SLUG, [$page, 'silenceNotices']) !== false)->toBeTrue();
});
