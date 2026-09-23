<?php

declare(strict_types=1);

use Brain\Monkey;

uses()
    ->beforeEach(function (): void {
        Monkey\setUp();
    })
    ->afterEach(function (): void {
        Monkey\tearDown();
    })
    ->in('Unit');
