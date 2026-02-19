<?php

if (! defined('ABSPATH')) {
    exit;
}

require_once WP_WEDPLAN_DIR . 'includes/class-wp-wedplan-admin.php';
require_once WP_WEDPLAN_DIR . 'includes/class-wp-wedplan-shortcodes.php';
require_once WP_WEDPLAN_DIR . 'includes/class-wp-wedplan-rest.php';

class WP_WedPlan_Plugin
{
    private WP_WedPlan_Admin $admin;
    private WP_WedPlan_Shortcodes $shortcodes;
    private WP_WedPlan_REST $rest;

    public function __construct()
    {
        $this->admin = new WP_WedPlan_Admin();
        $this->shortcodes = new WP_WedPlan_Shortcodes();
        $this->rest = new WP_WedPlan_REST();
    }

    public function run(): void
    {
        add_action('init', [$this, 'register_post_types']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets']);

        $this->admin->register_hooks();
        $this->shortcodes->register_hooks();
        $this->rest->register_hooks();
    }

    public function register_post_types(): void
    {
        register_post_type('wedplan_expense', [
            'labels' => [
                'name' => __('Dépenses', 'wp-wedplan'),
                'singular_name' => __('Dépense', 'wp-wedplan'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_rest' => true,
            'supports' => ['title', 'author'],
            'menu_icon' => 'dashicons-money-alt',
        ]);
    }

    public function enqueue_public_assets(): void
    {
        wp_enqueue_style('wp-wedplan-style', WP_WEDPLAN_URL . 'assets/css/wp-wedplan.css', [], WP_WEDPLAN_VERSION);
        wp_enqueue_script('wp-wedplan-script', WP_WEDPLAN_URL . 'assets/js/wp-wedplan.js', ['jquery'], WP_WEDPLAN_VERSION, true);

        wp_localize_script('wp-wedplan-script', 'wpWedPlan', [
            'apiBase' => esc_url_raw(rest_url('wp-wedplan/v1')),
            'nonce' => wp_create_nonce('wp_rest'),
            'currency' => get_option('wp_wedplan_currency', 'EUR'),
        ]);
    }
}
