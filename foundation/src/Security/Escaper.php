<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Foundation\Security;

/**
 * @since 2.0.0
 */
final class Escaper
{
    /**
     * @since 2.0.0
     */
    public function html(string $value): string
    {
        return esc_html($value);
    }

    /**
     * @since 2.0.0
     */
    public function attr(string $value): string
    {
        return esc_attr($value);
    }

    /**
     * @since 2.0.0
     */
    public function url(string $value): string
    {
        return esc_url($value);
    }

    /**
     * @since 2.0.0
     */
    public function js(string $value): string
    {
        return esc_js($value);
    }

    /**
     * @since 2.0.0
     */
    public function textarea(string $value): string
    {
        return esc_textarea($value);
    }
}
