<?php

if (! defined('ABSPATH')) {
    exit;
}

class WP_WedPlan_Shortcodes
{
    public function register_hooks(): void
    {
        add_shortcode('wp_wedplan_dashboard', [$this, 'render_dashboard_shortcode']);
    }

    public function render_dashboard_shortcode(): string
    {
        if (! is_user_logged_in()) {
            return '<p>' . esc_html__('Connectez-vous pour accéder à votre planificateur de mariage.', 'wp-wedplan') . '</p>';
        }

        $expenses = get_posts([
            'post_type' => 'wedplan_expense',
            'posts_per_page' => -1,
            'author' => get_current_user_id(),
            'post_status' => 'publish',
        ]);

        $budget_total = 0.0;
        $paid_total = 0.0;

        foreach ($expenses as $expense) {
            $amount = (float) get_post_meta($expense->ID, 'wedplan_amount', true);
            $budget_total += $amount;

            if (get_post_meta($expense->ID, 'wedplan_status', true) === 'paid') {
                $paid_total += $amount;
            }
        }

        $remaining = max($budget_total - $paid_total, 0);
        $currency = get_option('wp_wedplan_currency', 'EUR');

        ob_start();
        ?>
        <div class="wp-wedplan-grid">
            <div class="wp-wedplan-card"><h3><?php esc_html_e('Budget total', 'wp-wedplan'); ?></h3><p><?php echo esc_html(number_format_i18n($budget_total, 2) . ' ' . $currency); ?></p></div>
            <div class="wp-wedplan-card"><h3><?php esc_html_e('Total payé', 'wp-wedplan'); ?></h3><p><?php echo esc_html(number_format_i18n($paid_total, 2) . ' ' . $currency); ?></p></div>
            <div class="wp-wedplan-card"><h3><?php esc_html_e('Reste à payer', 'wp-wedplan'); ?></h3><p><?php echo esc_html(number_format_i18n($remaining, 2) . ' ' . $currency); ?></p></div>
            <div class="wp-wedplan-card"><h3><?php esc_html_e('Nombre de postes', 'wp-wedplan'); ?></h3><p><?php echo esc_html((string) count($expenses)); ?></p></div>
        </div>

        <div class="wp-wedplan-expenses">
            <h3><?php esc_html_e('Dépenses', 'wp-wedplan'); ?></h3>
            <ul>
                <?php foreach ($expenses as $expense) : ?>
                    <?php $status = get_post_meta($expense->ID, 'wedplan_status', true) ?: 'pending'; ?>
                    <?php $amount = (float) get_post_meta($expense->ID, 'wedplan_amount', true); ?>
                    <li>
                        <strong><?php echo esc_html($expense->post_title); ?></strong>
                        — <?php echo esc_html(number_format_i18n($amount, 2) . ' ' . $currency); ?>
                        <em>(<?php echo esc_html($status); ?>)</em>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php

        return (string) ob_get_clean();
    }
}
