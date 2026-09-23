<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Tests\Unit\Foundation;

use Brain\Monkey\Functions;
use InstallmentPricesForWooCommerce\Foundation\Plugin;

beforeEach(function (): void {
    Functions\when('get_file_data')->justReturn(['Name' => 'Plugin', 'Version' => '1.0.0', 'TextDomain' => 'wporg-slug', 'DomainPath' => '', 'RequiresPHP' => '', 'RequiresWP' => '']);
});

it('names options after the text domain by default', function (): void {
    $plugin = Plugin::create('/plugins/wporg-slug/wporg-slug.php');

    expect($plugin->slug())->toBe('wporg-slug')
        ->and($plugin->optionPrefix())->toBe('wporg_slug');
});

it('names options after a slug of its own, leaving the text domain alone', function (): void {
    $plugin = Plugin::create('/plugins/wporg-slug/wporg-slug.php', 'current-name');

    expect($plugin->slug())->toBe('current-name')
        ->and($plugin->optionPrefix())->toBe('current_name')
        ->and($plugin->textDomain())->toBe('wporg-slug');
});
