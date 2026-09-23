<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Settings;

/**
 * Translates version 1.x's stored data into 2.x's shapes. 1.x saved checkboxes as
 * '1', the out-of-stock choices as 'yes'/'no', amounts with a decimal comma, and the
 * discount type as 0 (percentage) or 1 (fixed). The results are raw input: the 2.x
 * sanitizers validate them like anything else.
 *
 * @since 2.0.0
 */
final class LegacySettings
{
    /**
     * 1.x named each style field "<kind>_<context>_<part>_<property>".
     *
     * @since 2.0.0
     */
    private const STYLE_NAMES = [
        'kinds'      => ['installments' => 'installments', 'cash' => 'in_cash'],
        'contexts'   => ['loop' => 'in_loop', 'single' => 'in_single'],
        'properties' => ['color' => 'color', 'weight' => 'font-weight', 'size' => 'font-size'],
    ];

    /**
     * @since 2.0.0
     *
     * @param array<array-key, mixed> $old The fswp_settings row.
     *
     * @return array<string, mixed>
     */
    public static function toSettings(array $old): array
    {
        $style = [];

        foreach (self::STYLE_NAMES['kinds'] as $kind => $oldKind) {
            foreach (self::STYLE_NAMES['contexts'] as $context => $oldContext) {
                foreach (Settings::PARTS as $part) {
                    foreach (self::STYLE_NAMES['properties'] as $property => $oldProperty) {
                        $style[$kind][$context][$part][$property] = $old["{$oldKind}_{$oldContext}_{$part}_{$oldProperty}"] ?? '';
                    }
                }
            }
        }

        return [
            'installments' => [
                'enabled'      => self::checked($old['enable_installments'] ?? null),
                'prefix'       => $old['installment_prefix'] ?? '',
                'max'          => $old['installment_qty'] ?? null,
                'suffix'       => $old['installment_suffix'] ?? '',
                'min_amount'   => $old['installment_minimum_value'] ?? 0,
                'out_of_stock' => ($old['enable_installments_if_out_of_stock'] ?? '') === 'yes',
            ],
            'cash'         => [
                'enabled'       => self::checked($old['enable_in_cash'] ?? null),
                'prefix'        => $old['in_cash_prefix'] ?? '',
                'discount'      => $old['in_cash_discount'] ?? 0,
                'discount_type' => self::discountType($old['in_cash_discount_type'] ?? null) ?? 'percent',
                'suffix'        => $old['in_cash_suffix'] ?? '',
                'out_of_stock'  => ($old['enable_in_cash_if_out_of_stock'] ?? '') === 'yes',
            ],
            'placement'    => [
                'loop'   => [
                    'hook'     => $old['fswp_in_loop_position'] ?? null,
                    'priority' => $old['fswp_in_loop_position_level'] ?? null,
                    'align'    => $old['in_loop_alignment'] ?? null,
                ],
                'single' => [
                    'hook'     => $old['fswp_in_single_position'] ?? null,
                    'priority' => $old['fswp_in_single_position_level'] ?? null,
                    'align'    => $old['in_single_alignment'] ?? null,
                ],
            ],
            'style'        => $style,
        ];
    }

    /**
     * An unset field (1.x left it empty) falls back to the store setting.
     *
     * @since 2.0.0
     *
     * @param array<array-key, mixed> $old A product's fswp_post_meta.
     *
     * @return array<string, mixed>
     */
    public static function toOverrides(array $old): array
    {
        return [
            'installments_disabled' => self::checked($old['disable_installments'] ?? null),
            'max'                   => $old['installment_qty'] ?? '',
            'cash_disabled'         => self::checked($old['disable_in_cash'] ?? null),
            'discount'              => $old['in_cash_discount'] ?? '',
            'discount_type'         => self::discountType($old['in_cash_discount_type'] ?? null) ?? '',
        ];
    }

    /**
     * @since 2.0.0
     */
    private static function checked(mixed $value): bool
    {
        return is_scalar($value) && (string) $value === '1';
    }

    /**
     * Null when 1.x left the type unset.
     *
     * @since 2.0.0
     */
    private static function discountType(mixed $value): ?string
    {
        if (! is_scalar($value) || (string) $value === '') {
            return null;
        }

        return (string) $value === '1' ? 'fixed' : 'percent';
    }
}
