<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Tests\Unit\Admin;

use Brain\Monkey\Functions;
use InstallmentPricesForWooCommerce\Foundation\Hooks\HookRegistrar;
use InstallmentPricesForWooCommerce\Module\AdminUi\AdminPage;

beforeEach(function (): void {
    Functions\when('add_query_arg')->alias(
        fn (string $key, string $value, string $url): string => $url . (str_contains($url, '?') ? '&' : '?') . $key . '=' . $value
    );
    Functions\when('admin_url')->alias(fn (string $path): string => 'https://example.test/wp-admin/' . $path);
});

// Named: Brain Monkey can't record a hook callback on an anonymous class.
final class ExamplePage extends AdminPage
{
    public function __construct(private readonly ?string $parent, private readonly bool $silences = false)
    {
    }

    protected function slug(): string
    {
        return 'example';
    }

    protected function pageTitle(): string
    {
        return 'Example';
    }

    protected function menuTitle(): string
    {
        return 'Example';
    }

    protected function renderBody(): void
    {
    }

    protected function parentSlug(): ?string
    {
        return $this->parent;
    }

    protected function silencesNotices(): bool
    {
        return $this->silences;
    }
}

it('links the page on its parent screen, or on admin.php for a menu slug', function (?string $parent, string $url): void {
    expect((new ExamplePage($parent))->url())->toBe($url);
})->with([
    'top-level page'     => [null, 'https://example.test/wp-admin/admin.php?page=example'],
    'core screen parent' => ['options-general.php', 'https://example.test/wp-admin/options-general.php?page=example'],
    'menu slug parent'   => ['woocommerce', 'https://example.test/wp-admin/admin.php?page=example'],
    'post type parent'   => ['edit.php?post_type=product', 'https://example.test/wp-admin/edit.php?post_type=product&page=example'],
]);

it('silences notices on its own screen only when the page opts in', function (bool $silences): void {
    Functions\when('add_submenu_page')->justReturn('settings_page_example');
    $page = new ExamplePage('options-general.php', $silences);

    $page->register();

    expect(has_action('load-settings_page_example', [$page, 'silenceNotices']) !== false)->toBe($silences);
})->with([
    'opted in'   => [true],
    'by default' => [false],
]);

it('adds itself to the menu once subscribed', function (): void {
    $page = new ExamplePage('options-general.php');

    (new HookRegistrar('tests'))->register($page);

    expect(has_action('admin_menu', [$page, 'register']) !== false)->toBeTrue();
});
