<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Providers;

use InstallmentPricesForWooCommerce\Foundation\Plugin;
use InstallmentPricesForWooCommerce\Identity;
use InstallmentPricesForWooCommerce\Foundation\Provider\ServiceProvider;
use InstallmentPricesForWooCommerce\Settings\Settings;
use InstallmentPricesForWooCommerce\Storefront\Renderer;
use InstallmentPricesForWooCommerce\Storefront\Styles;

/**
 * @since 2.0.0
 */
final class StorefrontServiceProvider extends ServiceProvider
{
    /**
     * @since 2.0.0
     */
    public function register(): void
    {
        $container = $this->container;

        $container->singleton(Renderer::class);
        $container->singleton(Styles::class, static fn (): Styles => new Styles(
            $container->make(Settings::class),
            Identity::SLUG,
            $container->make(Plugin::class)->version(),
        ));
    }

    /**
     * WHY wired by hand: the hooks and priorities are settings, which a compile-time
     * #[Action] can't express. With both lines switched off, nothing is hooked.
     *
     * @since 2.0.0
     */
    public function boot(): void
    {
        $settings = $this->container->make(Settings::class)->all();

        if (! $settings['installments']['enabled'] && ! $settings['cash']['enabled']) {
            return;
        }

        $renderer = $this->container->make(Renderer::class);

        add_action($settings['placement']['loop']['hook'], [$renderer, 'loop'], $settings['placement']['loop']['priority']);
        add_action($settings['placement']['single']['hook'], [$renderer, 'single'], $settings['placement']['single']['priority']);
        add_filter('woocommerce_available_variation', [$renderer, 'variation'], 10, 3);
        add_action('wp_enqueue_scripts', [$this->container->make(Styles::class), 'enqueue']);
    }
}
