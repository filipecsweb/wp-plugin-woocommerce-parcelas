<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Module\AdminUi;

use InstallmentPricesForWooCommerce\Foundation\Assets\Vite;
use InstallmentPricesForWooCommerce\Foundation\I18n\TextDomain;

/**
 * Enqueues a Vite-built admin bundle, scoped to a single admin screen, so it
 * never loads on any other wp-admin page.
 *
 * @since 2.0.0
 */
final class AdminAssets
{
    /**
     * @since 2.0.0
     */
    public function __construct(private readonly Vite $vite)
    {
    }

    /**
     * @since 2.0.0
     *
     * @param non-empty-string     $handle
     * @param array<string, mixed> $localize Data exposed to JS as a global object.
     */
    public function enqueueOnScreen(
        string $pageHookSuffix,
        string $currentHookSuffix,
        string $entry,
        string $handle,
        string $localizeObject = '',
        array $localize = [],
        ?TextDomain $textDomain = null
    ): void {
        if ($pageHookSuffix === '' || $currentHookSuffix !== $pageHookSuffix) {
            return;
        }

        $this->vite->enqueueScript($entry, $handle);
        $textDomain?->loadForScript($handle);

        if ($localizeObject !== '' && $localize !== []) {
            // Printed as a classic inline script before the module, so the module
            // can read window.{localizeObject} on execution.
            wp_localize_script($handle, $localizeObject, $localize);
        }
    }
}
