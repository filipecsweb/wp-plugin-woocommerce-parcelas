<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Foundation\Http;

use WP_Error;

/**
 * A small, immutable wrapper around the WordPress HTTP API (wp_remote_*).
 *
 * with*() methods return clones, so a configured client can be safely shared
 * and specialised per request without leaking state.
 *
 * @since 2.0.0
 */
final class HttpClient
{
    /**
     * @since 2.0.0
     *
     * @var array<string, string>
     */
    private array $headers;

    /**
     * @since 2.0.0
     *
     * @param array<string, string> $headers
     */
    public function __construct(
        private string $baseUrl = '',
        array $headers = [],
        private int $timeout = 15,
    ) {
        $this->headers = $headers;
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * @since 2.0.0
     */
    public function withBaseUrl(string $baseUrl): self
    {
        $clone          = clone $this;
        $clone->baseUrl = rtrim($baseUrl, '/');

        return $clone;
    }

    /**
     * @since 2.0.0
     */
    public function withToken(string $token): self
    {
        return $this->withHeaders(['Authorization' => 'Bearer ' . $token]);
    }

    /**
     * @since 2.0.0
     *
     * @param array<string, string> $headers
     */
    public function withHeaders(array $headers): self
    {
        $clone          = clone $this;
        $clone->headers = array_merge($this->headers, $headers);

        return $clone;
    }

    /**
     * @since 2.0.0
     */
    public function withTimeout(int $seconds): self
    {
        $clone          = clone $this;
        $clone->timeout = $seconds;

        return $clone;
    }

    /**
     * @since 2.0.0
     *
     * @param array<string, scalar> $query
     */
    public function get(string $path, array $query = []): Response
    {
        $url = $this->url($path);

        if ($query !== []) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
        }

        return $this->request('GET', $url, null);
    }

    /**
     * @since 2.0.0
     *
     * @param array<string, mixed> $body
     */
    public function post(string $path, array $body = []): Response
    {
        return $this->request('POST', $this->url($path), $body);
    }

    /**
     * @since 2.0.0
     *
     * @param array<string, mixed>|null $body
     */
    public function request(string $method, string $url, ?array $body = null): Response
    {
        $headers = array_merge(['Accept' => 'application/json'], $this->headers);

        $args = [
            'method'  => $method,
            'timeout' => $this->timeout,
            'headers' => $headers,
        ];

        if ($body !== null) {
            $args['headers']['Content-Type'] = 'application/json';
            $encoded                         = wp_json_encode($body);
            $args['body']                    = is_string($encoded) ? $encoded : '';
        }

        $response = wp_remote_request($url, $args);

        if ($response instanceof WP_Error) {
            return Response::fromError($response);
        }

        $rawHeaders = wp_remote_retrieve_headers($response);

        if (is_array($rawHeaders)) {
            $headerData = $rawHeaders;
        } elseif (is_object($rawHeaders) && method_exists($rawHeaders, 'getAll')) {
            /** @var array<string, mixed> $headerData */
            $headerData = $rawHeaders->getAll();
        } else {
            $headerData = [];
        }

        return new Response(
            (int) wp_remote_retrieve_response_code($response),
            (string) wp_remote_retrieve_body($response),
            $headerData,
        );
    }

    /**
     * @since 2.0.0
     */
    private function url(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return $this->baseUrl . '/' . ltrim($path, '/');
    }
}
