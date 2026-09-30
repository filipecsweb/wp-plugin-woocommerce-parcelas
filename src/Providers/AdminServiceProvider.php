<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Providers;

use InstallmentPricesForWooCommerce\Admin\ProductDataTab;
use InstallmentPricesForWooCommerce\Admin\SettingsPage;
use InstallmentPricesForWooCommerce\Foundation\Plugin;
use InstallmentPricesForWooCommerce\Foundation\Provider\ServiceProvider;

/**
 * @since 2.0.0
 */
final class AdminServiceProvider extends ServiceProvider
{
    /**
     * @since 2.0.0
     *
     * @var list<class-string>
     */
    protected array $subscribers = [ProductDataTab::class, SettingsPage::class];

    /**
     * @since 2.0.0
     */
    public function register(): void
    {
        $this->container->singleton(SettingsPage::class);
    }

    /**
     * WHY the action links by hand: the hook name embeds the runtime plugin basename,
     * which a compile-time #[Filter] attribute can't express.
     *
     * @since 2.0.0
     */
    public function boot(): void
    {
        parent::boot();

        $basename = $this->container->make(Plugin::class)->basename();

        add_filter('plugin_action_links_' . $basename, [$this->container->make(SettingsPage::class), 'pluginActionLinks']);
    }
}
