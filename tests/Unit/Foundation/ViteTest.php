<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Tests\Unit\Foundation;

use Brain\Monkey\Functions;
use InstallmentPricesForWooCommerce\Foundation\Assets\Vite;
use InstallmentPricesForWooCommerce\Foundation\Assets\ViteException;

beforeEach(function (): void {
    $this->buildPath = sys_get_temp_dir() . '/vite-test-' . uniqid();
    mkdir($this->buildPath . '/.vite', 0777, true);
    file_put_contents($this->buildPath . '/.vite/manifest.json', (string) json_encode([
        'resources/js/app.tsx' => ['file' => 'app.js', 'isEntry' => true],
    ]));

    $this->sidecar = function (string $json): void {
        file_put_contents($this->buildPath . '/.vite/wp-deps.json', $json);
    };

    $this->vite = new Vite($this->buildPath, 'https://example.test/build', '1.1.0', 'plugin');

    $this->scripts = [];
    Functions\when('wp_enqueue_script')->alias(function (string $handle, string $src, array $deps): void {
        $this->scripts[$handle] = $deps;
    });
    Functions\when('wp_enqueue_style')->justReturn();
});

afterEach(function (): void {
    array_map('unlink', glob($this->buildPath . '/.vite/*') ?: []);
    rmdir($this->buildPath . '/.vite');
    rmdir($this->buildPath);
});

it('passes the given deps through when there is no sidecar', function (): void {
    $this->vite->enqueueScript('resources/js/app.tsx', 'plugin-app', ['jquery']);

    expect($this->scripts)->toBe(['plugin-app' => ['jquery']]);
});

it('merges the entry sidecar deps without duplicates', function (): void {
    ($this->sidecar)('{"resources/js/app.tsx": ["react", "wp-i18n"]}');

    $this->vite->enqueueScript('resources/js/app.tsx', 'plugin-app', ['wp-i18n', 'jquery']);

    expect($this->scripts)->toBe(['plugin-app' => ['wp-i18n', 'jquery', 'react']]);
});

it('adds nothing for an entry the sidecar does not list', function (): void {
    ($this->sidecar)('{"resources/js/other.tsx": ["react"]}');

    $this->vite->enqueueScript('resources/js/app.tsx', 'plugin-app', ['jquery']);

    expect($this->scripts)->toBe(['plugin-app' => ['jquery']]);
});

it('rejects a malformed sidecar', function (string $json): void {
    ($this->sidecar)($json);

    expect(fn () => $this->vite->enqueueScript('resources/js/app.tsx', 'plugin-app'))->toThrow(ViteException::class)
        ->and($this->scripts)->toBe([]);
})->with([
    'invalid JSON'   => '{"resources/js/app.tsx": [',
    'not an object'  => '"react"',
    'handle map'     => '{"resources/js/app.tsx": {"a": "react"}}',
    'non-string'     => '{"resources/js/app.tsx": ["react", 1]}',
]);

it('tags only the entry script as a module, whatever the attribute order', function (string $entry): void {
    $this->vite->enqueueScript('resources/js/app.tsx', 'plugin-app');

    $inline = "<script id=\"plugin-app-js-translations\">\nwp.i18n.setLocaleData({});\n</script>\n"
        . "<script id=\"plugin-app-js-before\">\nvar before = 1;\n</script>\n";
    $after  = "\n<script id=\"plugin-app-js-after\">\nvar after = 1;\n</script>\n";

    $filtered = $this->vite->filterModuleTag($inline . $entry . $after, 'plugin-app', 'https://example.test/build/app.js');

    expect($filtered)->toBe($inline . '<script type="module"' . substr($entry, 7) . $after);
})->with([
    'WP 6.6 (src first)' => '<script src="https://example.test/build/app.js?ver=1.1.0" id="plugin-app-js"></script>',
    'WP 7.1 (id first)'  => '<script id="plugin-app-js" src="https://example.test/build/app.js?ver=1.1.0"></script>',
]);

it('replaces a non-module type on the entry and keeps an existing module type', function (): void {
    $this->vite->enqueueScript('resources/js/app.tsx', 'plugin-app');

    $classic = "<script type='text/javascript' src=\"app.js\" id=\"plugin-app-js\"></script>";
    $module  = '<script src="app.js" type="module" id="plugin-app-js"></script>';

    expect($this->vite->filterModuleTag($classic, 'plugin-app', 'app.js'))
        ->toBe('<script type="module" src="app.js" id="plugin-app-js"></script>')
        ->and($this->vite->filterModuleTag($module, 'plugin-app', 'app.js'))->toBe($module);
});

it('leaves scripts it did not enqueue untouched', function (): void {
    $tag = '<script src="other.js" id="other-js"></script>';

    expect($this->vite->filterModuleTag($tag, 'other', 'other.js'))->toBe($tag);
});
