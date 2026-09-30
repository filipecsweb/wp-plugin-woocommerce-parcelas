<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Foundation\Container;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

/**
 * Thrown when a container entry cannot be built or resolved.
 *
 * Not final: NotFoundException extends this so every container error shares a
 * single RuntimeException-rooted hierarchy (catchable as ContainerException or
 * \RuntimeException) while still implementing the correct PSR-11 interface.
 *
 * @since 2.0.0
 */
class ContainerException extends RuntimeException implements ContainerExceptionInterface
{
}
