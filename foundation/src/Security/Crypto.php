<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Foundation\Security;

// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The message contains only a libsodium byte-length constant; it is a boot-time configuration guard, never echoed as HTML.

/**
 * Authenticated symmetric encryption via libsodium (XSalsa20-Poly1305).
 *
 * The key is derived from the site's WordPress salts unless one is injected.
 * Because the key depends on wp_salt(), rotating the salts (e.g. regenerating
 * wp-config.php keys) makes existing ciphertext undecryptable — by design,
 * decrypt() returns NULL on any failure (wrong format, tampering, rotated
 * salts) and NEVER throws or fatals. Callers detect the null and trigger a
 * graceful "reconnect" flow rather than white-screening.
 *
 * @since 2.0.0
 */
final class Crypto
{
    /**
     * @since 2.0.0
     */
    private const PREFIX = 'ipfw:sb1:';

    /**
     * @since 2.0.0
     *
     * @param string|null $key Optional 32-byte key; derived from salts when null.
     *
     * @throws \InvalidArgumentException When an injected key is not exactly 32 bytes.
     */
    public function __construct(private readonly ?string $key = null)
    {
        if ($key !== null && strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new \InvalidArgumentException(sprintf(
                'Encryption key must be exactly %d bytes.',
                SODIUM_CRYPTO_SECRETBOX_KEYBYTES
            ));
        }
    }

    /**
     * sodium_memzero scrubs only this by-value copy; callers own their secret's
     * (and the option cache's) lifetime.
     *
     * @since 2.0.0
     */
    public function encrypt(string $plaintext): string
    {
        $nonce  = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = sodium_crypto_secretbox($plaintext, $nonce, $this->key());
        $token  = self::PREFIX . base64_encode($nonce . $cipher);

        sodium_memzero($plaintext);

        return $token;
    }

    /**
     * @since 2.0.0
     */
    public function decrypt(string $payload): ?string
    {
        if (! $this->isEncrypted($payload)) {
            return null;
        }

        $decoded = base64_decode(substr($payload, strlen(self::PREFIX)), true);

        if ($decoded === false) {
            return null;
        }

        $minimum = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES;

        if (strlen($decoded) < $minimum) {
            return null;
        }

        $nonce  = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        try {
            $plain = sodium_crypto_secretbox_open($cipher, $nonce, $this->key());
        } catch (\SodiumException) {
            // Belt-and-braces: the never-throws contract holds even if a future
            // change lets a malformed key reach this point.
            return null;
        }

        return $plain === false ? null : $plain;
    }

    /**
     * @since 2.0.0
     */
    public function isEncrypted(string $value): bool
    {
        return str_starts_with($value, self::PREFIX);
    }

    /**
     * @since 2.0.0
     */
    private function key(): string
    {
        if ($this->key !== null) {
            return $this->key;
        }

        $material = wp_salt('secure_auth') . wp_salt('auth');

        return sodium_crypto_generichash($material, '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
    }
}
