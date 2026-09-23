<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Foundation\I18n;

/**
 * Loads the plugin's translations.
 *
 * @since 2.0.0
 */
final class TextDomain
{
    /**
     * @since 2.0.0
     */
    public function __construct(
        private readonly string $domain,
        private readonly string $relativePath,
    ) {
    }

    /**
     * @since 2.0.0
     */
    public function load(): void
    {
        // phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound -- Retained so bundled /languages translations load on installs outside wordpress.org; on wordpress.org, translations also auto-load by slug.
        load_plugin_textdomain($this->domain, false, $this->relativePath);
    }

    /**
     * Registers a script's JSON translations; bundled ones are read from the same
     * directory as the PHP translations.
     *
     * @since 2.0.0
     */
    public function loadForScript(string $handle): void
    {
        wp_set_script_translations($handle, $this->domain, WP_PLUGIN_DIR . '/' . $this->relativePath);
    }
}
