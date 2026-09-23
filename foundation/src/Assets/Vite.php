<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Foundation\Assets;

// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- ViteException messages contain only internal manifest entry keys; they surface at build/boot via wp_die/log, never echoed as HTML.

/**
 * Vendored Vite asset enqueuer (no external dependency).
 *
 * The build manifest is read and the JS entry plus its CSS (including imported
 * chunks' CSS) are enqueued. The entry also depends on the core script handles an
 * optional wp-deps.json sidecar (written by the build, next to the manifest) lists
 * for it. Scripts are tagged as ES modules via a script_loader_tag filter.
 *
 * @since 2.0.0
 */
final class Vite
{
    /**
     * @since 2.0.0
     */
    private const DEPS_SIDECAR = 'wp-deps.json';

    /**
     * @since 2.0.0
     *
     * @var array<string, mixed>|null
     */
    private ?array $manifestCache = null;

    /**
     * @since 2.0.0
     *
     * @var array<string, mixed>|null
     */
    private ?array $depsCache = null;

    /**
     * @since 2.0.0
     *
     * @var array<string, bool>
     */
    private array $moduleHandles = [];

    /**
     * @since 2.0.0
     */
    private bool $moduleFilterAdded = false;

    /**
     * @since 2.0.0
     */
    public function __construct(
        private readonly string $buildPath,
        private readonly string $buildUrl,
        private readonly string $version = '',
        private readonly string $handlePrefix = 'vite',
    ) {
    }

    /**
     * Enqueue a JS entry (e.g. "resources/js/admin.js").
     *
     * @since 2.0.0
     *
     * @param non-empty-string       $handle
     * @param list<non-empty-string> $deps
     */
    public function enqueueScript(string $entry, string $handle, array $deps = [], bool $inFooter = true): void
    {
        $manifest = $this->manifest();

        if (! isset($manifest[$entry]) || ! is_array($manifest[$entry])) {
            throw new ViteException(sprintf('Vite entry "%s" was not found in the build manifest.', $entry));
        }

        /** @var array{file?: string} $chunk */
        $chunk = $manifest[$entry];

        if (! isset($chunk['file']) || ! is_string($chunk['file'])) {
            throw new ViteException(sprintf('Vite entry "%s" has no output file in the manifest.', $entry));
        }

        $styles = array_values(array_unique($this->collectStyles($entry, $manifest, [])));

        foreach ($styles as $style) {
            // Derive the style handle from the output filename so a CSS
            // chunk shared by multiple entries collapses to a single <link>
            // (WordPress dedupes styles by handle, not by URL).
            $styleHandle = $this->handlePrefix . '-' . sanitize_title(pathinfo($style, PATHINFO_FILENAME));
            wp_enqueue_style($styleHandle, $this->buildUrl . '/' . $style, [], $this->version);
        }

        $this->registerModuleHandle($handle);
        $deps = array_values(array_unique([...$deps, ...$this->coreDeps($entry)]));
        wp_enqueue_script($handle, $this->buildUrl . '/' . $chunk['file'], $deps, $this->version, $inFooter);
    }

    /**
     * Enqueue a standalone CSS entry.
     *
     * @since 2.0.0
     */
    public function enqueueStyle(string $entry, string $handle): void
    {
        $manifest = $this->manifest();
        $chunk    = $manifest[$entry] ?? null;

        if (is_array($chunk) && isset($chunk['file']) && is_string($chunk['file'])) {
            wp_enqueue_style($handle, $this->buildUrl . '/' . $chunk['file'], [], $this->version);
        }
    }

    /**
     * Core script handles (react, wp-i18n, …) the build left external for an entry.
     * No sidecar, or no key for the entry, means none.
     *
     * @since 2.0.0
     *
     * @return list<non-empty-string>
     */
    private function coreDeps(string $entry): array
    {
        $this->depsCache ??= $this->readBuildJson(self::DEPS_SIDECAR) ?? [];
        $deps              = $this->depsCache[$entry] ?? [];
        $handles           = is_array($deps) ? array_values(array_filter($deps, static fn (mixed $dep): bool => is_string($dep) && $dep !== '')) : [];

        if ($handles !== $deps) {
            throw new ViteException(sprintf('Vite deps sidecar has no list of script handles for "%s".', $entry));
        }

        return $handles;
    }

    /**
     * Recursively gather the CSS files for an entry and its imported chunks.
     *
     * @since 2.0.0
     *
     * @param array<string, mixed> $manifest
     * @param list<string>         $seen
     *
     * @return list<string>
     */
    private function collectStyles(string $entry, array $manifest, array $seen): array
    {
        if (in_array($entry, $seen, true)) {
            return [];
        }

        $seen[] = $entry;
        $chunk  = $manifest[$entry] ?? null;

        if (! is_array($chunk)) {
            return [];
        }

        $styles = [];

        foreach ((array) ($chunk['css'] ?? []) as $css) {
            if (is_string($css)) {
                $styles[] = $css;
            }
        }

        foreach ((array) ($chunk['imports'] ?? []) as $import) {
            if (is_string($import)) {
                $styles = array_merge($styles, $this->collectStyles($import, $manifest, $seen));
            }
        }

        return $styles;
    }

    /**
     * @since 2.0.0
     */
    private function registerModuleHandle(string $handle): void
    {
        $this->moduleHandles[$handle] = true;

        if (! $this->moduleFilterAdded) {
            add_filter('script_loader_tag', [$this, 'filterModuleTag'], 10, 3);
            $this->moduleFilterAdded = true;
        }
    }

    /**
     * @since 2.0.0
     */
    public function filterModuleTag(string $tag, string $handle, string $src): string
    {
        unset($src); // The original $tag already carries the (escaped) src.

        if (! isset($this->moduleHandles[$handle])) {
            return $tag;
        }

        // Only the entry's own tag becomes a module. The bundle also holds the handle's
        // translations and before/after inline scripts: they must stay classic so they
        // run ahead of the deferred module, which reads its locale data from them.
        $entryTag = '/<script\b[^>]*\sid=(["\'])' . preg_quote($handle . '-js', '/') . '\1[^>]*>/';

        return (string) preg_replace_callback(
            $entryTag,
            static function (array $match): string {
                if (preg_match('/\stype=(["\'])module\1/', $match[0]) === 1) {
                    return $match[0];
                }

                $open = (string) preg_replace('/\s+type=("|\')(?:.*?)\1/', '', $match[0]);

                return (string) preg_replace('/^<script\b/', '<script type="module"', $open);
            },
            $tag,
            1
        );
    }

    /**
     * @since 2.0.0
     *
     * @return array<string, mixed>
     */
    private function manifest(): array
    {
        return $this->manifestCache ??= $this->readBuildJson('manifest.json')
            ?? throw new ViteException('Vite build manifest not found. Run "npm run build".');
    }

    /**
     * Decodes a JSON file the build wrote to .vite/ (Vite 5+) or the build root.
     * Null when neither exists.
     *
     * @since 2.0.0
     *
     * @return array<string, mixed>|null
     */
    private function readBuildJson(string $name): ?array
    {
        foreach ([$this->buildPath . '/.vite/' . $name, $this->buildPath . '/' . $name] as $candidate) {
            if (! is_file($candidate)) {
                continue;
            }

            $json    = file_get_contents($candidate);
            $decoded = is_string($json) ? json_decode($json, true) : null;

            if (! is_array($decoded)) {
                throw new ViteException(sprintf('Vite build file "%s" is not a JSON object.', $name));
            }

            $data = [];

            foreach ($decoded as $key => $value) {
                $data[(string) $key] = $value;
            }

            return $data;
        }

        return null;
    }
}
