<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Foundation\Container;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;

// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Messages are built only from internal identifiers (service IDs, class names) and are caught and logged or surfaced as an escaped WP_Error/JSON response — never echoed as HTML. (PCP runs its own ruleset and cannot see the project phpcs.xml exclusion.)

/**
 * A minimal PSR-11 container with constructor autowiring.
 *
 * - bind()/singleton()/instance() register entries.
 * - get() resolves only registered entries (PSR-11 contract: has() === true).
 * - make() additionally autowires any instantiable class via reflection.
 *
 * @since 2.0.0
 */
final class Container implements ContainerInterface
{
    /**
     * @since 2.0.0
     *
     * @var array<string, Closure(self, array<string, mixed>): mixed>
     */
    private array $bindings = [];

    /**
     * @since 2.0.0
     *
     * @var array<string, bool>
     */
    private array $shared = [];

    /**
     * @since 2.0.0
     *
     * @var array<string, mixed>
     */
    private array $instances = [];

    /**
     * @since 2.0.0
     *
     * @var array<string, true>
     */
    private array $building = [];

    /**
     * @since 2.0.0
     *
     * @param Closure(self, array<string, mixed>): mixed|class-string|null $concrete
     */
    public function bind(string $id, Closure|string|null $concrete = null, bool $shared = false): void
    {
        unset($this->instances[$id]);

        $concrete ??= $id;

        if (! $concrete instanceof Closure) {
            $target   = $concrete;
            $concrete = static fn (self $container, array $parameters = []): mixed
                => $container->build($target, $parameters);
        }

        $this->bindings[$id] = $concrete;
        $this->shared[$id]   = $shared;
    }

    /**
     * @since 2.0.0
     *
     * @param Closure(self, array<string, mixed>): mixed|class-string|null $concrete
     */
    public function singleton(string $id, Closure|string|null $concrete = null): void
    {
        $this->bind($id, $concrete, true);
    }

    /**
     * @since 2.0.0
     */
    public function instance(string $id, mixed $instance): void
    {
        $this->instances[$id] = $instance;
        $this->shared[$id]    = true;
    }

    /**
     * Resolve an entry, autowiring unregistered-but-instantiable classes.
     *
     * @since 2.0.0
     *
     * @template TMake of object
     *
     * @param class-string<TMake>|string $id
     * @param array<string, mixed>       $parameters Only honoured when an instance
     *        is actually built. Shared entries (singleton/instance) cache the first
     *        build, so $parameters passed on later calls are ignored — and get()
     *        never forwards parameters at all.
     *
     * @return ($id is class-string<TMake> ? TMake : mixed)
     */
    public function make(string $id, array $parameters = []): mixed
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (isset($this->building[$id])) {
            throw new ContainerException(sprintf(
                'Circular dependency detected while resolving "%s": %s.',
                $id,
                implode(' -> ', [...array_keys($this->building), $id])
            ));
        }

        $this->building[$id] = true;

        try {
            if (isset($this->bindings[$id])) {
                $object = ($this->bindings[$id])($this, $parameters);
            } elseif (class_exists($id)) {
                $object = $this->build($id, $parameters);
            } else {
                throw new NotFoundException(sprintf('Service "%s" is not bound and cannot be resolved.', $id));
            }
        } finally {
            unset($this->building[$id]);
        }

        if ($this->shared[$id] ?? false) {
            $this->instances[$id] = $object;
        }

        return $object;
    }

    /**
     * @since 2.0.0
     *
     * @template TGet of object
     *
     * @param class-string<TGet>|string $id
     *
     * @return ($id is class-string<TGet> ? TGet : mixed)
     */
    public function get(string $id): mixed
    {
        if (! $this->has($id)) {
            throw new NotFoundException(sprintf('Service "%s" is not registered in the container.', $id));
        }

        return $this->make($id);
    }

    /**
     * @since 2.0.0
     */
    public function has(string $id): bool
    {
        return isset($this->bindings[$id]) || array_key_exists($id, $this->instances);
    }

    /**
     * @since 2.0.0
     */
    public function forget(string $id): void
    {
        unset($this->bindings[$id], $this->shared[$id], $this->instances[$id]);
    }

    /**
     * Instantiate a concrete class, resolving its constructor dependencies.
     *
     * @since 2.0.0
     *
     * @param array<array-key, mixed> $parameters
     */
    public function build(string $concrete, array $parameters = []): object
    {
        if (! class_exists($concrete)) {
            throw new ContainerException(sprintf('Target class "%s" does not exist.', $concrete));
        }

        $reflector = new ReflectionClass($concrete);

        if (! $reflector->isInstantiable()) {
            throw new ContainerException(sprintf('Target "%s" is not instantiable.', $concrete));
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return $reflector->newInstance();
        }

        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {
            $arguments[] = $this->resolveParameter($parameter, $parameters, $concrete);
        }

        return $reflector->newInstanceArgs($arguments);
    }

    /**
     * @since 2.0.0
     *
     * @param array<array-key, mixed> $parameters
     */
    private function resolveParameter(ReflectionParameter $parameter, array $parameters, string $concrete): mixed
    {
        $name = $parameter->getName();

        if (array_key_exists($name, $parameters)) {
            return $parameters[$name];
        }

        $type = $parameter->getType();

        // Autowire a single class/interface dependency, but only when it can
        // actually be resolved; otherwise fall through to the default/null
        // handling so "?Foo $x = null" works for unbound types. Union and
        // intersection types are not ReflectionNamedType and skip autowiring.
        if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
            $typeName = $type->getName();

            if ($this->has($typeName) || class_exists($typeName) || interface_exists($typeName)) {
                try {
                    return $this->make($typeName);
                } catch (ContainerExceptionInterface $exception) {
                    if ($parameter->isDefaultValueAvailable()) {
                        return $parameter->getDefaultValue();
                    }

                    if ($parameter->allowsNull()) {
                        return null;
                    }

                    // Surface a transitive failure as a ContainerException so
                    // get() never throws NotFoundException for an id that has().
                    throw new ContainerException(sprintf(
                        'Unable to resolve dependency "%s" of "%s": %s',
                        $typeName,
                        $concrete,
                        $exception->getMessage()
                    ), 0, $exception);
                }
            }
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->allowsNull()) {
            return null;
        }

        throw new ContainerException(sprintf(
            'Unable to resolve dependency "$%s" while building "%s".',
            $name,
            $concrete
        ));
    }
}
