<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Foundation\Hooks;

use LogicException;
use ReflectionObject;

// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The message is built only from reflected class/method names and surfaces at boot via wp_die/log — never echoed as HTML.

/**
 * Discovers #[Action] / #[Filter] attributes on a service's methods and wires
 * them to WordPress via add_action()/add_filter().
 *
 * Cost model and caching:
 *  - Hook *firing* never reflects: WordPress invokes the plain [$service, method]
 *    callback directly.
 *  - Discovery (reflection) runs at most ONCE PER REQUEST per class thanks to the
 *    request-level memo, and is skipped ENTIRELY on a persistent object cache hit
 *    (Redis/Memcached, as on most managed hosts). The compiled map is keyed by
 *    class + plugin version, so it invalidates automatically on deploy.
 *  - The persistent cache is bypassed under WP_DEBUG so developers always see
 *    fresh attribute discovery while iterating.
 *
 * @since 2.0.0
 */
final class HookRegistrar
{
    /**
     * @since 2.0.0
     *
     * @var array<class-string, list<array{type:string, hook:string, method:string, priority:int, args:int}>>
     */
    private array $memo = [];

    /**
     * @since 2.0.0
     */
    public function __construct(
        private readonly string $cacheGroup,
        private readonly string $cacheVersion = '',
    ) {
    }

    /**
     * @since 2.0.0
     */
    public function register(object $service): void
    {
        foreach ($this->discover($service) as $binding) {
            $callback = [$service, $binding['method']];

            if (! is_callable($callback)) {
                continue;
            }

            if ($binding['type'] === 'action') {
                add_action($binding['hook'], $callback, $binding['priority'], $binding['args']);
            } else {
                add_filter($binding['hook'], $callback, $binding['priority'], $binding['args']);
            }
        }
    }

    /**
     * @since 2.0.0
     *
     * @return list<array{type: string, hook: string, method: string, priority: int, args: int}>
     */
    private function discover(object $service): array
    {
        $class = $service::class;

        if (isset($this->memo[$class])) {
            return $this->memo[$class];
        }

        $usePersistentCache = function_exists('wp_using_ext_object_cache')
            && wp_using_ext_object_cache()
            && ! (defined('WP_DEBUG') && WP_DEBUG);

        $cacheKey = $class . '@' . $this->cacheVersion;

        if ($usePersistentCache) {
            $cached = wp_cache_get($cacheKey, $this->cacheGroup);

            if (is_array($cached)) {
                return $this->memo[$class] = $this->normalize($cached);
            }
        }

        $bindings = $this->reflect($service);

        if ($usePersistentCache) {
            wp_cache_set($cacheKey, $bindings, $this->cacheGroup);
        }

        return $this->memo[$class] = $bindings;
    }

    /**
     * Coerce a cached array back into the binding shape (wp_cache_get is mixed).
     *
     * @since 2.0.0
     *
     * @param array<mixed> $rows
     *
     * @return list<array{type: string, hook: string, method: string, priority: int, args: int}>
     */
    private function normalize(array $rows): array
    {
        $bindings = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $type   = $row['type'] ?? '';
            $hook   = $row['hook'] ?? '';
            $method = $row['method'] ?? '';

            if (! is_string($type) || ! is_string($hook) || ! is_string($method) || $method === '') {
                continue;
            }

            $priority = $row['priority'] ?? 10;
            $args     = $row['args'] ?? 1;

            $bindings[] = [
                'type'     => $type === 'filter' ? 'filter' : 'action',
                'hook'     => $hook,
                'method'   => $method,
                'priority' => is_numeric($priority) ? (int) $priority : 10,
                'args'     => is_numeric($args) ? (int) $args : 1,
            ];
        }

        return $bindings;
    }

    /**
     * The cold path: reflect over the service's methods once.
     *
     * @since 2.0.0
     *
     * @return list<array{type: string, hook: string, method: string, priority: int, args: int}>
     */
    private function reflect(object $service): array
    {
        $reflection = new ReflectionObject($service);
        $bindings   = [];

        foreach ($reflection->getMethods() as $method) {
            $actions = $method->getAttributes(Action::class);
            $filters = $method->getAttributes(Filter::class);

            if ($actions === [] && $filters === []) {
                continue;
            }

            // Fail loud at registration rather than as a deferred "call to
            // non-public method" fatal when the hook eventually fires.
            if (! $method->isPublic()) {
                throw new LogicException(sprintf(
                    '%s::%s() has a #[Action]/#[Filter] attribute but is not public; hook callbacks must be public.',
                    $reflection->getName(),
                    $method->getName()
                ));
            }

            foreach ($actions as $attribute) {
                $action     = $attribute->newInstance();
                $bindings[] = [
                    'type'     => 'action',
                    'hook'     => $action->hook,
                    'method'   => $method->getName(),
                    'priority' => $action->priority,
                    'args'     => $action->acceptedArgs,
                ];
            }

            foreach ($filters as $attribute) {
                $filter     = $attribute->newInstance();
                $bindings[] = [
                    'type'     => 'filter',
                    'hook'     => $filter->hook,
                    'method'   => $method->getName(),
                    'priority' => $filter->priority,
                    'args'     => $filter->acceptedArgs,
                ];
            }
        }

        return $bindings;
    }
}
