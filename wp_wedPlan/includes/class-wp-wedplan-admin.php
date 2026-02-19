<?php

if (! defined('ABSPATH')) {
    exit;
}

class WP_WedPlan_Admin
{
    public function register_hooks(): void
    {
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function register_admin_menu(): void
    {
        add_menu_page(
            __('WedPlan', 'wp-wedplan'),
            __('WedPlan', 'wp-wedplan'),
            'manage_options',
            'wp-wedplan',
            [$this, 'render_settings_page'],
            'dashicons-heart',
            26
        );
    }

    public function register_settings(): void
    {
        register_setting('wp_wedplan_settings', 'wp_wedplan_currency', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'EUR',
        ]);

        register_setting('wp_wedplan_settings', 'wp_wedplan_default_budget', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '10000',
        ]);

        register_setting('wp_wedplan_settings', 'wp_wedplan_wedding_date', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        ]);
    }

    public function render_settings_page(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Paramètres WP WedPlan', 'wp-wedplan'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('wp_wedplan_settings'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="wp_wedplan_currency"><?php esc_html_e('Devise', 'wp-wedplan'); ?></label></th>
                        <td><input name="wp_wedplan_currency" id="wp_wedplan_currency" value="<?php echo esc_attr(get_option('wp_wedplan_currency', 'EUR')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wp_wedplan_default_budget"><?php esc_html_e('Budget par défaut', 'wp-wedplan'); ?></label></th>
                        <td><input name="wp_wedplan_default_budget" id="wp_wedplan_default_budget" value="<?php echo esc_attr(get_option('wp_wedplan_default_budget', '10000')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wp_wedplan_wedding_date"><?php esc_html_e('Date du mariage', 'wp-wedplan'); ?></label></th>
                        <td><input type="date" name="wp_wedplan_wedding_date" id="wp_wedplan_wedding_date" value="<?php echo esc_attr(get_option('wp_wedplan_wedding_date', '')); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong><?php esc_html_e('Shortcode disponible :', 'wp-wedplan'); ?></strong> <code>[wp_wedplan_dashboard]</code></p>
        </div>
        <?php
    }
}
