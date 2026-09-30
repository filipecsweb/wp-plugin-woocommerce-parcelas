<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Foundation\Database;

use wpdb;

/**
 * version() must be stable and unique: the Migrator keys off it to run each
 * migration exactly once.
 *
 * @since 2.0.0
 */
abstract class Migration
{
    /**
     * @since 2.0.0
     */
    abstract public function version(): string;

    /**
     * @since 2.0.0
     */
    abstract public function up(): void;

    /**
     * @since 2.0.0
     */
    public function down(): void
    {
    }

    /**
     * @since 2.0.0
     */
    protected function dbDelta(string $sql): void
    {
        if (! function_exists('dbDelta')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        dbDelta($sql);
    }

    /**
     * @since 2.0.0
     */
    protected function charsetCollate(): string
    {
        return $this->wpdb()->get_charset_collate();
    }

    /**
     * @since 2.0.0
     */
    protected function tableName(string $name): string
    {
        return $this->wpdb()->prefix . $name;
    }

    /**
     * @since 2.0.0
     */
    protected function wpdb(): wpdb
    {
        /** @var wpdb $wpdb */
        global $wpdb;

        return $wpdb;
    }
}
