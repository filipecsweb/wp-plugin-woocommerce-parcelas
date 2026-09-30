<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Foundation\Provider;

use InstallmentPricesForWooCommerce\Foundation\Container\Container;
use InstallmentPricesForWooCommerce\Foundation\Contracts\ServiceProviderInterface;
use InstallmentPricesForWooCommerce\Foundation\Hooks\HookRegistrar;

/**
 * Subclasses overriding boot() MUST call parent::boot() or $subscribers'
 * attribute hooks won't wire.
 *
 * @since 2.0.0
 */
abstract class ServiceProvider implements ServiceProviderInterface
{
    /**
     * @since 2.0.0
     *
     * @var list<class-string>
     */
    protected array $subscribers = [];

    /**
     * @since 2.0.0
     */
    public function __construct(protected readonly Container $container)
    {
    }

    /**
     * @since 2.0.0
     */
    public function register(): void
    {
    }

    /**
     * @since 2.0.0
     */
    public function boot(): void
    {
        if ($this->subscribers === []) {
            return;
        }

        $registrar = $this->container->make(HookRegistrar::class);

        foreach ($this->subscribers as $subscriber) {
            $instance = $this->container->make($subscriber);

            if (is_object($instance)) {
                $registrar->register($instance);
            }
        }
    }
}
