<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Tests\Unit\Admin;

use Brain\Monkey\Functions;
use InstallmentPricesForWooCommerce\Module\AdminUi\AdminPage;

beforeEach(function (): void {
    Functions\when('add_query_arg')->alias(
        fn (string $key, string $value, string $url): string => $url . (str_contains($url, '?') ? '&' : '?') . $key . '=' . $value
    );
    Functions\when('admin_url')->alias(fn (string $path): string => 'https://example.test/wp-admin/' . $path);
});

it('links the page on its parent screen, or on admin.php for a menu slug', function (?string $parent, string $url): void {
    $page = new class ($parent) extends AdminPage {
        public function __construct(private readonly ?string $parent)
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
    };

    expect($page->url())->toBe($url);
})->with([
    'top-level page'     => [null, 'https://example.test/wp-admin/admin.php?page=example'],
    'core screen parent' => ['options-general.php', 'https://example.test/wp-admin/options-general.php?page=example'],
    'menu slug parent'   => ['woocommerce', 'https://example.test/wp-admin/admin.php?page=example'],
    'post type parent'   => ['edit.php?post_type=product', 'https://example.test/wp-admin/edit.php?post_type=product&page=example'],
]);
