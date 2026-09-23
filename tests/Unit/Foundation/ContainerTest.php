<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Tests\Unit\Foundation;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use InstallmentPricesForWooCommerce\Foundation\Container\Container;
use InstallmentPricesForWooCommerce\Foundation\Container\ContainerException;
use InstallmentPricesForWooCommerce\Foundation\Container\NotFoundException;

final class CtDep
{
    public int $value = 42;
}

final class CtService
{
    public function __construct(public CtDep $dep)
    {
    }
}

interface CtThing
{
}

final class CtNeedsOptional
{
    public function __construct(public ?CtThing $thing = null, public int $n = 7)
    {
    }
}

final class CtA
{
    public function __construct(public CtB $b)
    {
    }
}

final class CtB
{
    public function __construct(public CtA $a)
    {
    }
}

final class CtNeedsUnbound
{
    public function __construct(public CtThing $thing)
    {
    }
}

it('returns the same instance for a singleton', function (): void {
    $c = new Container();
    $c->singleton('svc', static fn (): object => new \stdClass());

    expect($c->get('svc'))->toBe($c->get('svc'));
});

it('returns fresh instances for a plain bind', function (): void {
    $c = new Container();
    $c->bind('x', static fn (): object => new \stdClass());

    expect($c->make('x'))->not->toBe($c->make('x'));
});

it('autowires nested constructor dependencies', function (): void {
    $service = (new Container())->make(CtService::class);

    expect($service)->toBeInstanceOf(CtService::class)
        ->and($service->dep->value)->toBe(42);
});

it('uses the default for an optional unbound dependency', function (): void {
    $object = (new Container())->make(CtNeedsOptional::class);

    expect($object->thing)->toBeNull()
        ->and($object->n)->toBe(7);
});

it('has() is false and get() throws NotFound for an unregistered id', function (): void {
    $c = new Container();

    expect($c->has('missing'))->toBeFalse();
    $c->get('missing');
})->throws(NotFoundException::class);

it('surfaces a transitive resolution failure as a ContainerException, not NotFound', function (): void {
    $c = new Container();
    $c->bind('svc', CtNeedsUnbound::class);

    expect($c->has('svc'))->toBeTrue();

    try {
        $c->get('svc');
        $this->fail('Expected a container exception.');
    } catch (NotFoundExceptionInterface $e) {
        $this->fail('Transitive failure must not be NotFound.');
    } catch (ContainerExceptionInterface $e) {
        expect($e)->toBeInstanceOf(ContainerException::class);
    }
});

it('detects circular dependencies cleanly', function (): void {
    (new Container())->make(CtA::class);
})->throws(ContainerException::class, 'Circular dependency');

it('unifies the exception hierarchy', function (): void {
    $exception = new NotFoundException('x');

    expect($exception)->toBeInstanceOf(ContainerException::class)
        ->and($exception)->toBeInstanceOf(\RuntimeException::class)
        ->and($exception)->toBeInstanceOf(NotFoundExceptionInterface::class);
});
