<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

if (! defined('ABSPATH')) {
    define('ABSPATH', sys_get_temp_dir() . '/');
}

if (! defined('WP_PLUGIN_DIR')) {
    define('WP_PLUGIN_DIR', ABSPATH . 'wp-content/plugins');
}

// The one WooCommerce type the unit tests construct. Not the full stubs: those define
// WooCommerce's functions too, which Brain Monkey must be free to stub per test.
if (! class_exists('WC_Product')) {
    // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
    class WC_Product
    {
        public function __construct(private readonly int $id = 0)
        {
        }

        public function get_id(): int
        {
            return $this->id;
        }
    }
}
