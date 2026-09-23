<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Rest;

use InstallmentPricesForWooCommerce\Foundation\Rest\RestController;
use InstallmentPricesForWooCommerce\Foundation\Security\Capability;
use InstallmentPricesForWooCommerce\Providers\RestServiceProvider;
use InstallmentPricesForWooCommerce\Settings\Settings;
use WP_REST_Request;
use WP_REST_Response;

/**
 * /settings reads and writes the whole settings object; a POST may carry any part of
 * it, and the response is the full, validated result.
 *
 * @since 2.0.0
 */
final class SettingsController extends RestController
{
    /**
     * @since 2.0.0
     */
    public function __construct(
        string $namespace,
        Capability $capability,
        private readonly Settings $settings,
    ) {
        parent::__construct($namespace, $capability);
    }

    /**
     * @since 2.0.0
     */
    public function registerRoutes(): void
    {
        $this->registerRoute('/settings', [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'show'],
                'permission_callback' => $this->guard(RestServiceProvider::CAPABILITY),
            ],
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'save'],
                'permission_callback' => $this->guard(RestServiceProvider::CAPABILITY),
            ],
        ]);
    }

    /**
     * @since 2.0.0
     */
    public function show(): WP_REST_Response
    {
        return $this->respond($this->settings->all());
    }

    /**
     * @since 2.0.0
     *
     * @param WP_REST_Request<array<string, mixed>> $request
     */
    public function save(WP_REST_Request $request): WP_REST_Response
    {
        $input = $request->get_json_params();

        return $this->respond($this->settings->save(is_array($input) ? $input : []));
    }
}
