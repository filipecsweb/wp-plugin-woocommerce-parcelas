<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Pricing;

use InstallmentPricesForWooCommerce\Settings\Settings;

/**
 * @since 2.0.0
 */
final class Calculator
{
    /**
     * The most installments, up to $max, that keep each one at or above $minAmount;
     * null when not even the minimum number of installments does. Installment amounts
     * are compared as the store shows them, rounded to its price decimals.
     *
     * @since 2.0.0
     *
     * @return array{count: int, amount: float}|null
     */
    public static function installments(float $price, int $max, float $minAmount, int $decimals): ?array
    {
        if ($price <= 0) {
            return null;
        }

        $count = max(Settings::MIN_INSTALLMENTS, $max);

        while ($count > Settings::MIN_INSTALLMENTS && round($price / $count, $decimals) < $minAmount) {
            $count--;
        }

        $amount = $price / $count;

        return round($amount, $decimals) >= $minAmount ? ['count' => $count, 'amount' => $amount] : null;
    }

    /**
     * The price for paying in full at once; null when the discount leaves nothing to pay.
     *
     * @since 2.0.0
     */
    public static function cash(float $price, float $discount, string $type): ?float
    {
        if ($price <= 0) {
            return null;
        }

        $cash = $type === 'fixed' ? $price - $discount : $price * (1 - min(100.0, $discount) / 100);

        return $cash > 0 ? $cash : null;
    }
}
