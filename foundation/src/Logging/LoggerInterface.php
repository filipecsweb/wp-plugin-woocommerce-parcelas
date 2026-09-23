<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Foundation\Logging;

use Stringable;

/**
 * PSR-3-style logger interface (kept dependency-free; not the psr/log package).
 *
 * @since 2.0.0
 */
interface LoggerInterface
{
    /**
     * @since 2.0.0
     *
     * @param array<string, mixed> $context
     */
    public function log(string $level, string|Stringable $message, array $context = []): void;

    /**
     * @since 2.0.0
     *
     * @param array<string, mixed> $context
     */
    public function emergency(string|Stringable $message, array $context = []): void;

    /**
     * @since 2.0.0
     *
     * @param array<string, mixed> $context
     */
    public function alert(string|Stringable $message, array $context = []): void;

    /**
     * @since 2.0.0
     *
     * @param array<string, mixed> $context
     */
    public function critical(string|Stringable $message, array $context = []): void;

    /**
     * @since 2.0.0
     *
     * @param array<string, mixed> $context
     */
    public function error(string|Stringable $message, array $context = []): void;

    /**
     * @since 2.0.0
     *
     * @param array<string, mixed> $context
     */
    public function warning(string|Stringable $message, array $context = []): void;

    /**
     * @since 2.0.0
     *
     * @param array<string, mixed> $context
     */
    public function notice(string|Stringable $message, array $context = []): void;

    /**
     * @since 2.0.0
     *
     * @param array<string, mixed> $context
     */
    public function info(string|Stringable $message, array $context = []): void;

    /**
     * @since 2.0.0
     *
     * @param array<string, mixed> $context
     */
    public function debug(string|Stringable $message, array $context = []): void;
}
