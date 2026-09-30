<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Foundation\Rest;

use InstallmentPricesForWooCommerce\Foundation\Security\Capability;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * guard() expects the 'wp_rest' nonce in the X-WP-Nonce header (how the admin
 * UI sends it).
 *
 * @since 2.0.0
 */
abstract class RestController
{
    /**
     * @since 2.0.0
     */
    public function __construct(
        protected readonly string $namespace,
        protected readonly Capability $capability,
    ) {
    }

    /**
     * @since 2.0.0
     */
    public function hook(): void
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    /**
     * @since 2.0.0
     */
    abstract public function registerRoutes(): void;

    /**
     * @since 2.0.0
     *
     * @param array<array-key, mixed> $args
     */
    protected function registerRoute(string $route, array $args): void
    {
        // Guard against empty/falsy values so PHPStan sees non-falsy strings and
        // register_rest_route() never receives an invalid namespace or route.
        if ($this->namespace === '' || $this->namespace === '0' || $route === '' || $route === '0') {
            return;
        }

        register_rest_route($this->namespace, $route, $args);
    }

    /**
     * Build a permission_callback enforcing nonce + capability.
     *
     * @since 2.0.0
     *
     * @return callable(WP_REST_Request<array<string, mixed>>): (bool|WP_Error)
     */
    protected function guard(string $capability): callable
    {
        return function (WP_REST_Request $request) use ($capability): bool|WP_Error {
            $nonce = $request->get_header('X-WP-Nonce');

            if (! is_string($nonce) || wp_verify_nonce($nonce, 'wp_rest') === false) {
                return new WP_Error(
                    'rest_invalid_nonce',
                    'Invalid or missing security token.',
                    ['status' => 403]
                );
            }

            if (! $this->capability->can($capability)) {
                return new WP_Error(
                    'rest_forbidden',
                    'You are not allowed to perform this action.',
                    ['status' => 403]
                );
            }

            return true;
        };
    }

    /**
     * Read a request parameter as a string (get_param() returns mixed).
     *
     * @since 2.0.0
     *
     * @param WP_REST_Request<array<string, mixed>> $request
     */
    protected function stringParam(WP_REST_Request $request, string $key): string
    {
        $value = $request->get_param($key);

        return is_string($value) ? $value : '';
    }

    /**
     * @since 2.0.0
     */
    protected function respond(mixed $data, int $status = 200): WP_REST_Response
    {
        return new WP_REST_Response($data, $status);
    }

    /**
     * @since 2.0.0
     */
    protected function error(string $code, string $message, int $status = 400): WP_Error
    {
        return new WP_Error($code, $message, ['status' => $status]);
    }
}
