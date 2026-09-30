<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Foundation\Hooks;

use Attribute;

/**
 * CONTRACT: the target method must return the (possibly modified) filtered value.
 *
 * @since 2.0.0
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Filter
{
    /**
     * @since 2.0.0
     */
    public function __construct(
        public readonly string $hook,
        public readonly int $priority = 10,
        public readonly int $acceptedArgs = 1,
    ) {
    }
}
