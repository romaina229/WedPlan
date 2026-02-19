<?php
/**
 * Plugin Name: WP WedPlan
 * Description: Plugin complet de planification de mariage: gestion de budget, dépenses, tableau de bord, API REST et widgets shortcode.
 * Version: 1.0.0
 * Author: WedPlan Team
 * Text Domain: wp-wedplan
 */

if (! defined('ABSPATH')) {
    exit;
}

define('WP_WEDPLAN_VERSION', '1.0.0');
define('WP_WEDPLAN_DIR', plugin_dir_path(__FILE__));
define('WP_WEDPLAN_URL', plugin_dir_url(__FILE__));

require_once WP_WEDPLAN_DIR . 'includes/class-wp-wedplan-activator.php';
require_once WP_WEDPLAN_DIR . 'includes/class-wp-wedplan-plugin.php';

register_activation_hook(__FILE__, ['WP_WedPlan_Activator', 'activate']);

function wp_wedplan_bootstrap(): void
{
    $plugin = new WP_WedPlan_Plugin();
    $plugin->run();
}

wp_wedplan_bootstrap();
