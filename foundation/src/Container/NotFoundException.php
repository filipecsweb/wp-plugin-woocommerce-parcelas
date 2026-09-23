<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Foundation\Container;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Thrown when a requested container entry does not exist.
 *
 * Extends ContainerException so it is caught by `catch (ContainerException)` and
 * `catch (\RuntimeException)` alike, while implementing NotFoundExceptionInterface
 * (which extends ContainerExceptionInterface) to satisfy PSR-11.
 *
 * @since 2.0.0
 */
final class NotFoundException extends ContainerException implements NotFoundExceptionInterface
{
}
