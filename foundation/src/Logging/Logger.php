<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Foundation\Logging;

use Stringable;

/**
 * minLevel gates output so production noise can be tuned; context is
 * interpolated PSR-3 {placeholder} style.
 *
 * @since 2.0.0
 */
final class Logger implements LoggerInterface
{
    /**
     * @since 2.0.0
     *
     * @var array<string, int>
     */
    private const LEVELS = [
        LogLevel::DEBUG     => 0,
        LogLevel::INFO      => 1,
        LogLevel::NOTICE    => 2,
        LogLevel::WARNING   => 3,
        LogLevel::ERROR     => 4,
        LogLevel::CRITICAL  => 5,
        LogLevel::ALERT     => 6,
        LogLevel::EMERGENCY => 7,
    ];

    /**
     * @since 2.0.0
     */
    public function __construct(
        private readonly string $channel,
        private readonly string $minLevel = LogLevel::DEBUG,
    ) {
    }

    /**
     * @since 2.0.0
     *
     * @param array<string, mixed> $context
     */
    public function log(string $level, string|Stringable $message, array $context = []): void
    {
        if (! $this->shouldLog($level)) {
            return;
        }

        $line = sprintf(
            '[%s] %s.%s: %s',
            gmdate('c'),
            $this->channel,
            $level,
            $this->interpolate((string) $message, $context)
        );

        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        error_log($line);
    }

    /**
     * @since 2.0.0
     *
     * @param array<string, mixed> $context
     */
    public function emergency(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * @since 2.0.0
     *
     * @param array<string, mixed> $context
     */
    public function alert(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * @since 2.0.0
     *
     * @param array<string, mixed> $context
     */
    public function critical(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * @since 2.0.0
     *
     * @param array<string, mixed> $context
     */
    public function error(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * @since 2.0.0
     *
     * @param array<string, mixed> $context
     */
    public function warning(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * @since 2.0.0
     *
     * @param array<string, mixed> $context
     */
    public function notice(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * @since 2.0.0
     *
     * @param array<string, mixed> $context
     */
    public function info(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * @since 2.0.0
     *
     * @param array<string, mixed> $context
     */
    public function debug(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * @since 2.0.0
     */
    private function shouldLog(string $level): bool
    {
        $current   = self::LEVELS[$level] ?? 0;
        $threshold = self::LEVELS[$this->minLevel] ?? 0;

        return $current >= $threshold;
    }

    /**
     * @since 2.0.0
     *
     * @param array<string, mixed> $context
     */
    private function interpolate(string $message, array $context): string
    {
        if ($context === []) {
            return $message;
        }

        $replacements = [];

        foreach ($context as $key => $value) {
            if (is_scalar($value) || $value === null || $value instanceof Stringable) {
                $replacements['{' . $key . '}'] = $value === null ? 'null' : (string) $value;
            }
        }

        return strtr($message, $replacements);
    }
}
