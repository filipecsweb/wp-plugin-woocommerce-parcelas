<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Providers;

use InstallmentPricesForWooCommerce\Admin\ProductDataTab;
use InstallmentPricesForWooCommerce\Admin\SettingsPage;
use InstallmentPricesForWooCommerce\Foundation\Assets\Vite;
use InstallmentPricesForWooCommerce\Foundation\I18n\TextDomain;
use InstallmentPricesForWooCommerce\Foundation\Plugin;
use InstallmentPricesForWooCommerce\Foundation\Provider\ServiceProvider;
use InstallmentPricesForWooCommerce\Identity;
use InstallmentPricesForWooCommerce\Module\AdminUi\AdminAssets;
use InstallmentPricesForWooCommerce\Settings\Settings;

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
    protected array $subscribers = [ProductDataTab::class];

    /**
     * @since 2.0.0
     */
    public function register(): void
    {
        $this->container->singleton(SettingsPage::class);
    }

    /**
     * @since 2.0.0
     */
    public function boot(): void
    {
        parent::boot();

        $page   = $this->container->make(SettingsPage::class);
        $plugin = $this->container->make(Plugin::class);

        add_action('admin_menu', [$page, 'register']);

        // WHY manual: the hook name embeds the runtime plugin basename, which a
        // compile-time #[Filter] attribute can't express.
        add_filter('plugin_action_links_' . $plugin->basename(), [$page, 'pluginActionLinks']);

        add_action('admin_enqueue_scripts', function (string $hookSuffix) use ($page): void {
            $assets = new AdminAssets($this->container->make(Vite::class));

            $assets->enqueueOnScreen(
                $page->hookSuffix(),
                $hookSuffix,
                'resources/js/app/main.tsx',
                Identity::SLUG . '-app',
                'InstallmentPricesConfig',
                $this->config(),
                $this->container->make(TextDomain::class)
            );
        });
    }

    /**
     * CONTRACT: the keys are the Config type in resources/js/app/store.ts; keep the
     * two in step.
     *
     * @since 2.0.0
     *
     * @return array<string, mixed>
     */
    private function config(): array
    {
        $plugin = $this->container->make(Plugin::class);

        return [
            'restNamespace' => RestServiceProvider::NAMESPACE,
            'plugin'        => ['name' => $plugin->name(), 'version' => $plugin->version()],
            'settings'      => $this->container->make(Settings::class)->all(),
            'choices'       => Settings::choices(),
            'currency'      => html_entity_decode(get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8'),
            'supportUrl'    => 'https://wordpress.org/support/plugin/woocommerce-parcelas/',
        ];
    }
}
