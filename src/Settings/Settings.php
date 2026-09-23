<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Settings;

use InstallmentPricesForWooCommerce\Foundation\Settings\Options;

/**
 * The plugin's settings: their shape, defaults and validation, over one autoloaded
 * option row. Storage, the REST API and the admin screen share the shape, keys
 * included.
 *
 * @since 2.0.0
 *
 * @phpstan-type PartStyle array{color: string, weight: string, size: string}
 * @phpstan-type Placement array{hook: string, priority: int, align: string}
 * @phpstan-type Installments array{enabled: bool, prefix: string, max: int, suffix: string, min_amount: float, out_of_stock: bool}
 * @phpstan-type Cash array{enabled: bool, prefix: string, discount: float, discount_type: string, suffix: string, out_of_stock: bool}
 * @phpstan-type Values array{
 *     installments: Installments,
 *     cash: Cash,
 *     placement: array{loop: Placement, single: Placement},
 *     style: array<string, array<string, array<string, PartStyle>>>
 * }
 * @phpstan-type Choice array{value: string, label: string}
 */
final class Settings
{
    /**
     * @since 2.0.0
     */
    public const KINDS = ['installments', 'cash'];

    /**
     * @since 2.0.0
     */
    public const CONTEXTS = ['loop', 'single'];

    /**
     * @since 2.0.0
     */
    public const PARTS = ['prefix', 'amount', 'suffix'];

    /**
     * @since 2.0.0
     */
    public const LOOP_HOOKS = ['woocommerce_after_shop_loop_item_title', 'woocommerce_after_shop_loop_item'];

    /**
     * @since 2.0.0
     */
    public const SINGLE_HOOKS = [
        'woocommerce_single_product_summary',
        'woocommerce_before_add_to_cart_form',
        'woocommerce_before_add_to_cart_button',
        'woocommerce_after_add_to_cart_button',
        'woocommerce_after_add_to_cart_form',
    ];

    /**
     * An empty value leaves the theme's own alignment, weight or discount in place.
     *
     * @since 2.0.0
     */
    public const ALIGNMENTS = ['', 'left', 'center', 'right'];

    /**
     * @since 2.0.0
     */
    public const WEIGHTS = ['', '100', '200', '300', '400', '500', '600', '700', '800', '900'];

    /**
     * @since 2.0.0
     */
    public const DISCOUNT_TYPES = ['percent', 'fixed'];

    /**
     * @since 2.0.0
     */
    public const MIN_INSTALLMENTS = 2;

    /**
     * WHY 15: WooCommerce prints the price at priority 10 in both product lists and
     * the product summary, so 15 lands right after it.
     *
     * @since 2.0.0
     */
    private const DEFAULT_PRIORITY = 15;

    /**
     * @since 2.0.0
     */
    private const CSS_SIZE = '/^(?:\d+|\d*\.\d+)(?:px|em|rem|%|pt|vw|vh)$/';

    /**
     * @since 2.0.0
     *
     * @var Values|null
     */
    private ?array $cache = null;

    /**
     * @since 2.0.0
     */
    public function __construct(private readonly Options $options)
    {
    }

    /**
     * @since 2.0.0
     *
     * @return Values
     */
    public static function defaults(): array
    {
        return self::sanitize([]);
    }

    /**
     * @since 2.0.0
     *
     * @return Values
     */
    public function all(): array
    {
        return $this->cache ??= self::sanitize($this->options->all());
    }

    /**
     * Merges $input over the current settings, so a partial update keeps the rest.
     *
     * @since 2.0.0
     *
     * @param array<array-key, mixed> $input
     *
     * @return Values
     */
    public function save(array $input): array
    {
        $this->cache = self::sanitize(array_replace_recursive($this->all(), $input));
        $this->options->fill($this->cache);

        return $this->cache;
    }

    /**
     * Stores the settings on the first run: 1.x's when a store upgrades, otherwise
     * the defaults. Afterwards the autoloaded row exists, so this costs nothing.
     *
     * @since 2.0.0
     */
    public function install(): void
    {
        if ($this->options->all() !== []) {
            return;
        }

        $legacy      = get_option(StorageKeys::LEGACY_SETTINGS);
        $this->cache = self::sanitize(is_array($legacy) ? LegacySettings::toSettings($legacy) : []);
        $this->options->fill($this->cache);
    }

    /**
     * @since 2.0.0
     *
     * @return Values
     */
    public static function sanitize(mixed $input): array
    {
        $in           = is_array($input) ? $input : [];
        $installments = self::section($in, 'installments');
        $cash         = self::section($in, 'cash');
        $placement    = self::section($in, 'placement');
        $discountType = self::choice($cash['discount_type'] ?? null, self::DISCOUNT_TYPES, 'percent');
        $discount     = self::amount($cash['discount'] ?? 0);

        return [
            'installments' => [
                'enabled'      => self::flag($installments['enabled'] ?? false),
                'prefix'       => self::text($installments['prefix'] ?? ''),
                'max'          => max(self::MIN_INSTALLMENTS, self::integer($installments['max'] ?? null, self::MIN_INSTALLMENTS)),
                'suffix'       => self::text($installments['suffix'] ?? ''),
                'min_amount'   => self::amount($installments['min_amount'] ?? 0),
                'out_of_stock' => self::flag($installments['out_of_stock'] ?? false),
            ],
            'cash'         => [
                'enabled'       => self::flag($cash['enabled'] ?? false),
                'prefix'        => self::text($cash['prefix'] ?? ''),
                'discount'      => $discountType === 'percent' ? min(100.0, $discount) : $discount,
                'discount_type' => $discountType,
                'suffix'        => self::text($cash['suffix'] ?? ''),
                'out_of_stock'  => self::flag($cash['out_of_stock'] ?? false),
            ],
            'placement'    => [
                'loop'   => self::placement(self::section($placement, 'loop'), self::LOOP_HOOKS),
                'single' => self::placement(self::section($placement, 'single'), self::SINGLE_HOOKS),
            ],
            'style'        => self::style(self::section($in, 'style')),
        ];
    }

    /**
     * An amount as a store owner types it: a decimal comma is accepted (1.x stored
     * amounts that way, e.g. "5,95"); anything unreadable or negative is 0.
     *
     * @since 2.0.0
     */
    public static function amount(mixed $value): float
    {
        $number = is_string($value) ? str_replace(',', '.', trim($value)) : $value;

        return is_numeric($number) ? max(0.0, (float) $number) : 0.0;
    }

    /**
     * The admin screen's options for each choice field. Translated, so call it no
     * earlier than init. CONTRACT: each list's values are its constant above, in order.
     *
     * @since 2.0.0
     *
     * @return array{loopHooks: list<Choice>, singleHooks: list<Choice>, alignments: list<Choice>, weights: list<Choice>, discountTypes: list<Choice>}
     */
    public static function choices(): array
    {
        return [
            'loopHooks'     => self::labelled([
                'woocommerce_after_shop_loop_item_title' => __('Below the product title', 'woocommerce-parcelas'),
                'woocommerce_after_shop_loop_item'       => __('Below the Add to cart button', 'woocommerce-parcelas'),
            ]),
            'singleHooks'   => self::labelled([
                'woocommerce_single_product_summary'    => __('In the product summary', 'woocommerce-parcelas'),
                'woocommerce_before_add_to_cart_form'   => __('Above the add-to-cart form', 'woocommerce-parcelas'),
                'woocommerce_before_add_to_cart_button' => __('Above the Add to cart button', 'woocommerce-parcelas'),
                'woocommerce_after_add_to_cart_button'  => __('Below the Add to cart button', 'woocommerce-parcelas'),
                'woocommerce_after_add_to_cart_form'    => __('Below the add-to-cart form', 'woocommerce-parcelas'),
            ]),
            'alignments'    => self::labelled([
                ''       => __('Theme default', 'woocommerce-parcelas'),
                'left'   => __('Left', 'woocommerce-parcelas'),
                'center' => __('Center', 'woocommerce-parcelas'),
                'right'  => __('Right', 'woocommerce-parcelas'),
            ]),
            'weights'       => self::labelled([
                ''    => __('Theme default', 'woocommerce-parcelas'),
                '100' => __('100 (Thin)', 'woocommerce-parcelas'),
                '200' => __('200 (Extra light)', 'woocommerce-parcelas'),
                '300' => __('300 (Light)', 'woocommerce-parcelas'),
                '400' => __('400 (Normal)', 'woocommerce-parcelas'),
                '500' => __('500 (Medium)', 'woocommerce-parcelas'),
                '600' => __('600 (Semibold)', 'woocommerce-parcelas'),
                '700' => __('700 (Bold)', 'woocommerce-parcelas'),
                '800' => __('800 (Extra bold)', 'woocommerce-parcelas'),
                '900' => __('900 (Black)', 'woocommerce-parcelas'),
            ]),
            'discountTypes' => self::labelled([
                'percent' => __('Percentage (%)', 'woocommerce-parcelas'),
                'fixed'   => __('Fixed amount', 'woocommerce-parcelas'),
            ]),
        ];
    }

    /**
     * @since 2.0.0
     *
     * @param array<array-key, string> $labels Numeric keys arrive as ints.
     *
     * @return list<Choice>
     */
    private static function labelled(array $labels): array
    {
        $choices = [];

        foreach ($labels as $value => $label) {
            $choices[] = ['value' => (string) $value, 'label' => $label];
        }

        return $choices;
    }

    /**
     * @since 2.0.0
     *
     * @param array<array-key, mixed> $in
     * @param list<string>            $hooks
     *
     * @return Placement
     */
    private static function placement(array $in, array $hooks): array
    {
        return [
            'hook'     => self::choice($in['hook'] ?? null, $hooks, $hooks[0]),
            'priority' => self::integer($in['priority'] ?? null, self::DEFAULT_PRIORITY),
            'align'    => self::choice($in['align'] ?? null, self::ALIGNMENTS, ''),
        ];
    }

    /**
     * @since 2.0.0
     *
     * @param array<array-key, mixed> $in
     *
     * @return array<string, array<string, array<string, PartStyle>>>
     */
    private static function style(array $in): array
    {
        $style = [];

        foreach (self::KINDS as $kind) {
            foreach (self::CONTEXTS as $context) {
                foreach (self::PARTS as $part) {
                    $values = self::section(self::section(self::section($in, $kind), $context), $part);

                    $style[$kind][$context][$part] = [
                        'color'  => self::color($values['color'] ?? ''),
                        'weight' => self::choice($values['weight'] ?? null, self::WEIGHTS, ''),
                        'size'   => self::size($values['size'] ?? ''),
                    ];
                }
            }
        }

        return $style;
    }

    /**
     * @since 2.0.0
     *
     * @param array<array-key, mixed> $in
     *
     * @return array<array-key, mixed>
     */
    private static function section(array $in, string $key): array
    {
        return is_array($in[$key] ?? null) ? $in[$key] : [];
    }

    /**
     * @since 2.0.0
     */
    private static function flag(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @since 2.0.0
     */
    private static function text(mixed $value): string
    {
        return is_scalar($value) ? sanitize_text_field((string) $value) : '';
    }

    /**
     * @since 2.0.0
     */
    private static function integer(mixed $value, int $default): int
    {
        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * @since 2.0.0
     *
     * @param list<string> $allowed
     */
    private static function choice(mixed $value, array $allowed, string $default): string
    {
        $value = is_scalar($value) ? (string) $value : '';

        return in_array($value, $allowed, true) ? $value : $default;
    }

    /**
     * @since 2.0.0
     */
    private static function color(mixed $value): string
    {
        return is_string($value) ? (string) sanitize_hex_color(trim($value)) : '';
    }

    /**
     * @since 2.0.0
     */
    private static function size(mixed $value): string
    {
        $value = is_string($value) ? strtolower(trim($value)) : '';

        return preg_match(self::CSS_SIZE, $value) === 1 ? $value : '';
    }
}
