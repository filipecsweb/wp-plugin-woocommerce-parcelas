<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Tests\Unit\Admin;

use Brain\Monkey\Functions;
use InstallmentPricesForWooCommerce\Foundation\Assets\Vite;
use InstallmentPricesForWooCommerce\Foundation\I18n\TextDomain;
use InstallmentPricesForWooCommerce\Module\AdminUi\AdminAssets;

beforeEach(function (): void {
    $this->buildPath = sys_get_temp_dir() . '/admin-assets-test-' . uniqid();
    mkdir($this->buildPath . '/.vite', 0777, true);
    file_put_contents($this->buildPath . '/.vite/manifest.json', (string) json_encode([
        'resources/js/app.tsx' => ['file' => 'app.js', 'isEntry' => true],
    ]));

    $this->assets = new AdminAssets(new Vite($this->buildPath, 'https://example.test/build'));

    $this->translations = [];
    Functions\when('wp_enqueue_script')->justReturn();
    Functions\when('wp_set_script_translations')->alias(function (string ...$args): void {
        $this->translations[] = $args;
    });
    $this->inline = [];
    Functions\when('wp_json_encode')->alias(static fn (mixed $value, int $flags = 0): string|false => json_encode($value, $flags));
    Functions\when('wp_add_inline_script')->alias(function (string ...$args): bool {
        $this->inline[] = $args;

        return true;
    });
});

afterEach(function (): void {
    unlink($this->buildPath . '/.vite/manifest.json');
    rmdir($this->buildPath . '/.vite');
    rmdir($this->buildPath);
});

it('registers the script translations from the text domain directory', function (): void {
    $this->assets->enqueueOnScreen(
        'settings_page_x',
        'settings_page_x',
        'resources/js/app.tsx',
        'plugin-app',
        textDomain: new TextDomain('woocommerce-parcelas', 'woocommerce-parcelas/languages'),
    );

    expect($this->translations)->toBe([
        ['plugin-app', 'woocommerce-parcelas', WP_PLUGIN_DIR . '/woocommerce-parcelas/languages'],
    ]);
});

it('builds the global data only on its own screen', function (): void {
    $builds = 0;
    $config = function () use (&$builds): array {
        $builds++;

        return ['key' => 'value'];
    };

    $this->assets->enqueueOnScreen('settings_page_x', 'index.php', 'resources/js/app.tsx', 'plugin-app', 'PluginConfig', $config);
    expect($builds)->toBe(0);

    $this->assets->enqueueOnScreen('settings_page_x', 'settings_page_x', 'resources/js/app.tsx', 'plugin-app', 'PluginConfig', $config);
    expect($builds)->toBe(1);
});

it('prints the global data as JSON, so its scalars keep their types', function (): void {
    $this->assets->enqueueOnScreen(
        'settings_page_x',
        'settings_page_x',
        'resources/js/app.tsx',
        'plugin-app',
        'PluginConfig',
        static fn (): array => ['flag' => false, 'count' => 2, 'name' => '</script>'],
    );

    expect($this->inline)->toBe([
        ['plugin-app', 'var PluginConfig = {"flag":false,"count":2,"name":"\\u003C\\/script\\u003E"};', 'before'],
    ]);
});

it('registers no translations without a text domain', function (): void {
    $this->assets->enqueueOnScreen('settings_page_x', 'settings_page_x', 'resources/js/app.tsx', 'plugin-app');

    expect($this->translations)->toBe([]);
});
