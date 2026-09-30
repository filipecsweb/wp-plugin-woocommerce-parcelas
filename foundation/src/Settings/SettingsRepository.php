<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Foundation\Settings;

/**
 * Thin wrapper over the WordPress Settings API.
 *
 * Registering the option through register_setting() gives WordPress a known
 * sanitize callback and default even when the UI is driven by REST
 * rather than an option.php form.
 *
 * @since 2.0.0
 */
final class SettingsRepository
{
    /**
     * @since 2.0.0
     */
    public function __construct(private readonly string $group)
    {
    }

    /**
     * @since 2.0.0
     *
     * @param array{
     *     type?: string,
     *     description?: string,
     *     default?: mixed,
     *     sanitize_callback?: callable,
     *     show_in_rest?: bool|array<string, mixed>
     * } $args
     */
    public function register(string $option, array $args = []): void
    {
        register_setting($this->group, $option, $args);
    }

    /**
     * @since 2.0.0
     */
    public function unregister(string $option): void
    {
        unregister_setting($this->group, $option);
    }

    /**
     * @since 2.0.0
     */
    public function section(string $id, string $title, string $page, ?callable $render = null): void
    {
        add_settings_section($id, $title, $render ?? '__return_null', $page);
    }

    /**
     * @since 2.0.0
     *
     * @param array{label_for?: string, class?: string} $args
     */
    public function field(
        string $id,
        string $title,
        string $page,
        string $section,
        callable $render,
        array $args = []
    ): void {
        add_settings_field($id, $title, $render, $page, $section, $args);
    }
}
