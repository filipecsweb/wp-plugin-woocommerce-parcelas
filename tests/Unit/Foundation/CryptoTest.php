<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Tests\Unit\Foundation;

use InstallmentPricesForWooCommerce\Foundation\Security\Crypto;

const KEY_A = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'; // 32 bytes
const KEY_B = 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb'; // 32 bytes

it('round-trips a value', function (): void {
    $crypto = new Crypto(KEY_A);
    $cipher = $crypto->encrypt('super-secret-token');

    expect($cipher)->toStartWith('ipfw:sb1:')
        ->and($cipher)->not->toBe('super-secret-token')
        ->and($crypto->decrypt($cipher))->toBe('super-secret-token')
        ->and($crypto->isEncrypted($cipher))->toBeTrue();
});

it('returns null (never throws) when the key cannot decrypt', function (): void {
    $cipher = (new Crypto(KEY_A))->encrypt('secret');

    expect((new Crypto(KEY_B))->decrypt($cipher))->toBeNull();
});

it('returns null for non-encrypted or tampered input', function (): void {
    $crypto = new Crypto(KEY_A);

    expect($crypto->decrypt('not-encrypted'))->toBeNull()
        ->and($crypto->decrypt('ipfw:sb1:' . base64_encode('tooshort')))->toBeNull();
});

it('rejects a wrong-length injected key at construction', function (): void {
    new Crypto('too-short');
})->throws(\InvalidArgumentException::class);
