<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Foundation\Settings;

/**
 * Typed accessor over a single wp_options row stored as an associative array.
 *
 * Backed by one option key so the whole settings payload is read/written in a
 * single DB row. Reads are cached per request; writes refresh the cache.
 *
 * IMPORTANT: this row is AUTOLOADED — WordPress loads it via wp_load_alloptions()
 * on EVERY request — so it must hold only small, bounded configuration. Growing
 * or time-series data (e.g. the flush log) belongs in a dedicated table, never
 * here.
 *
 * @since 2.0.0
 */
final class Options
{
    /**
     * @since 2.0.0
     *
     * @var array<string, mixed>|null
     */
    private ?array $cache = null;

    /**
     * @since 2.0.0
     *
     * @param array<string, mixed> $defaults
     */
    public function __construct(
        private readonly string $name,
        private readonly array $defaults = [],
    ) {
    }

    /**
     * @since 2.0.0
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return array_merge($this->defaults, $this->load());
    }

    /**
     * @since 2.0.0
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $data = $this->all();

        return array_key_exists($key, $data) ? $data[$key] : $default;
    }

    /**
     * @since 2.0.0
     */
    public function getString(string $key, string $default = ''): string
    {
        $value = $this->get($key, $default);

        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * @since 2.0.0
     */
    public function getInt(string $key, int $default = 0): int
    {
        $value = $this->get($key, $default);

        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * @since 2.0.0
     */
    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default);

        if ($value === null) {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        return (bool) filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @since 2.0.0
     *
     * @param array<mixed> $default
     *
     * @return array<mixed>
     */
    public function getArray(string $key, array $default = []): array
    {
        $value = $this->get($key, $default);

        return is_array($value) ? $value : $default;
    }

    /**
     * @since 2.0.0
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    /**
     * @since 2.0.0
     */
    public function set(string $key, mixed $value): void
    {
        $this->fill([$key => $value]);
    }

    /**
     * @since 2.0.0
     *
     * @param array<string, mixed> $values
     */
    public function fill(array $values): void
    {
        $this->persist(array_merge($this->load(), $values));
    }

    /**
     * @since 2.0.0
     */
    public function forget(string $key): void
    {
        $data = $this->load();
        unset($data[$key]);
        $this->persist($data);
    }

    /**
     * @since 2.0.0
     */
    public function delete(): void
    {
        delete_option($this->name);
        $this->cache = null;
    }

    /**
     * @since 2.0.0
     *
     * @return array<string, mixed>
     */
    private function load(): array
    {
        if ($this->cache === null) {
            $stored = get_option($this->name, []);
            $data   = [];

            if (is_array($stored)) {
                foreach ($stored as $key => $value) {
                    $data[(string) $key] = $value;
                }
            }

            $this->cache = $data;
        }

        return $this->cache;
    }

    /**
     * @since 2.0.0
     *
     * @param array<string, mixed> $data
     */
    private function persist(array $data): void
    {
        // Autoload the settings blob: it is read on most admin (and some front)
        // requests. Explicit, rather than relying on WordPress's size heuristic.
        update_option($this->name, $data, true);
        $this->cache = $data;
    }
}
