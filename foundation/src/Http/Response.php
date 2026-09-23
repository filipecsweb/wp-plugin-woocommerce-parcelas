<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Foundation\Http;

use WP_Error;

/**
 * An immutable HTTP response value object wrapping a wp_remote_* result.
 *
 * @since 2.0.0
 */
final class Response
{
    /**
     * @since 2.0.0
     *
     * @param array<array-key, mixed> $headers
     */
    public function __construct(
        private readonly int $status,
        private readonly string $body,
        private readonly array $headers = [],
        private readonly ?string $error = null,
    ) {
    }

    /**
     * @since 2.0.0
     */
    public static function fromError(WP_Error $error): self
    {
        return new self(0, '', [], $error->get_error_message());
    }

    /**
     * @since 2.0.0
     */
    public function status(): int
    {
        return $this->status;
    }

    /**
     * @since 2.0.0
     */
    public function ok(): bool
    {
        return $this->error === null && $this->status >= 200 && $this->status < 300;
    }

    /**
     * @since 2.0.0
     */
    public function failed(): bool
    {
        return ! $this->ok();
    }

    /**
     * @since 2.0.0
     */
    public function body(): string
    {
        return $this->body;
    }

    /**
     * @since 2.0.0
     */
    public function error(): ?string
    {
        return $this->error;
    }

    /**
     * @since 2.0.0
     *
     * @return array<array-key, mixed>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * @since 2.0.0
     */
    public function json(): mixed
    {
        if ($this->body === '') {
            return null;
        }

        return json_decode($this->body, true);
    }

    /**
     * @since 2.0.0
     *
     * @return array<mixed>
     */
    public function array(): array
    {
        $decoded = $this->json();

        return is_array($decoded) ? $decoded : [];
    }
}
