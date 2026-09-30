<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Admin;

use InstallmentPricesForWooCommerce\Foundation\Hooks\Action;
use InstallmentPricesForWooCommerce\Foundation\I18n\TextDomain;
use InstallmentPricesForWooCommerce\Foundation\Plugin;
use InstallmentPricesForWooCommerce\Identity;
use InstallmentPricesForWooCommerce\Module\AdminUi\AdminAssets;
use InstallmentPricesForWooCommerce\Module\AdminUi\AdminPage;
use InstallmentPricesForWooCommerce\Providers\RestServiceProvider;
use InstallmentPricesForWooCommerce\Settings\Settings;

/**
 * The settings screen, under the WooCommerce menu. PHP prints the page chrome and the
 * React screen's mount; React renders the rest.
 *
 * @since 2.0.0
 */
final class SettingsPage extends AdminPage
{
    /**
     * Mount element of the React screen. CONTRACT: resources/js/app/main.tsx mounts
     * on this id and resources/css/app.css scopes its reset to it.
     *
     * @since 2.0.0
     */
    public const APP_ROOT_ID = Identity::SLUG . '-app';

    /**
     * @since 2.0.0
     */
    public function __construct(
        private readonly AdminAssets $assets,
        private readonly TextDomain $textDomain,
        private readonly Settings $settings,
        private readonly Plugin $plugin,
    ) {
    }

    /**
     * @since 2.0.0
     */
    protected function slug(): string
    {
        return Identity::SLUG;
    }

    /**
     * @since 2.0.0
     */
    protected function parentSlug(): string
    {
        return 'woocommerce';
    }

    /**
     * Gate the screen with the same capability the REST routes enforce, from the
     * one shared definition.
     *
     * @since 2.0.0
     */
    protected function capability(): string
    {
        return RestServiceProvider::CAPABILITY;
    }

    /**
     * WHY: other plugins' notices crowd the React screen, and hiding them with CSS would
     * reach outside its mount.
     *
     * @since 2.0.0
     */
    protected function silencesNotices(): bool
    {
        return true;
    }

    /**
     * @since 2.0.0
     */
    protected function accessDeniedMessage(): string
    {
        return __('Sorry, you are not allowed to access this page.', 'woocommerce-parcelas');
    }

    /**
     * @since 2.0.0
     */
    protected function pageTitle(): string
    {
        return __('Installment Prices for WooCommerce', 'woocommerce-parcelas');
    }

    /**
     * @since 2.0.0
     */
    protected function menuTitle(): string
    {
        return __('Installment Prices', 'woocommerce-parcelas');
    }

    /**
     * Prepends a Settings link to the plugin's row on the Plugins screen, matching
     * core's convention of listing it before Deactivate.
     *
     * @since 2.0.0
     *
     * @param array<string, string> $actions
     *
     * @return array<string, string>
     */
    public function pluginActionLinks(array $actions): array
    {
        $link = sprintf('<a href="%s">%s</a>', esc_url($this->url()), esc_html__('Settings', 'woocommerce-parcelas'));

        return ['settings' => $link] + $actions;
    }

    /**
     * @since 2.0.0
     */
    #[Action('admin_enqueue_scripts')]
    public function enqueueAssets(string $hookSuffix): void
    {
        $this->assets->enqueueOnScreen(
            $this->hookSuffix(),
            $hookSuffix,
            'resources/js/app/main.tsx',
            Identity::SLUG . '-app',
            'InstallmentPricesConfig',
            $this->config(...),
            $this->textDomain
        );
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
        return [
            'restNamespace' => RestServiceProvider::NAMESPACE,
            'plugin'        => ['name' => $this->plugin->name(), 'version' => $this->plugin->version()],
            'settings'      => $this->settings->all(),
            'choices'       => Settings::choices(),
            'currency'      => html_entity_decode(get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8'),
            'supportUrl'    => 'https://wordpress.org/support/plugin/woocommerce-parcelas/',
        ];
    }

    /**
     * @since 2.0.0
     */
    protected function renderBody(): void
    {
        printf(
            '<div class="wrap"><h1>%s</h1><p class="description">%s</p><div id="%s"></div></div>',
            esc_html($this->pageTitle()),
            esc_html__('Show installment prices and a cash price on your product lists and product pages.', 'woocommerce-parcelas'),
            esc_attr(self::APP_ROOT_ID)
        );
    }
}
