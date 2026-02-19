<?php

if (! defined('ABSPATH')) {
    exit;
}

class WP_WedPlan_Activator
{
    public static function activate(): void
    {
        self::create_logs_table();
        self::seed_options();
        flush_rewrite_rules();
    }

    private static function create_logs_table(): void
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wedplan_logs';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            action VARCHAR(120) NOT NULL,
            payload LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    private static function seed_options(): void
    {
        add_option('wp_wedplan_currency', 'EUR');
        add_option('wp_wedplan_default_budget', '10000');
        add_option('wp_wedplan_wedding_date', '');
    }
}
