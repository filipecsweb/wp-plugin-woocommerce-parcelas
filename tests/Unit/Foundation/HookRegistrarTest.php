<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Tests\Unit\Foundation;

use Brain\Monkey\Functions;
use InstallmentPricesForWooCommerce\Foundation\Hooks\Action;
use InstallmentPricesForWooCommerce\Foundation\Hooks\Filter;
use InstallmentPricesForWooCommerce\Foundation\Hooks\HookRegistrar;

final class HrSubscriber
{
    #[Action('save_post', priority: 20, acceptedArgs: 2)]
    public function onSave(int $id, mixed $post): void
    {
    }

    #[Filter('the_title')]
    public function title(string $title): string
    {
        return $title;
    }
}

final class HrBadSubscriber
{
    #[Action('init')]
    protected function notPublic(): void
    {
    }
}

beforeEach(function (): void {
    $GLOBALS['hr_actions'] = [];
    $GLOBALS['hr_filters'] = [];
    Functions\when('add_action')->alias(function ($hook): void {
        $GLOBALS['hr_actions'][] = $hook;
    });
    Functions\when('add_filter')->alias(function ($hook): void {
        $GLOBALS['hr_filters'][] = $hook;
    });
    Functions\when('wp_using_ext_object_cache')->justReturn(false);
});

it('wires attribute-declared actions and filters', function (): void {
    (new HookRegistrar('test_hooks'))->register(new HrSubscriber());

    expect($GLOBALS['hr_actions'])->toContain('save_post')
        ->and($GLOBALS['hr_filters'])->toContain('the_title');
});

it('fails loud on a non-public annotated method', function (): void {
    (new HookRegistrar('test_hooks'))->register(new HrBadSubscriber());
})->throws(\LogicException::class);
