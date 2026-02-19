<?php

if (! defined('ABSPATH')) {
    exit;
}

class WP_WedPlan_REST
{
    public function register_hooks(): void
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void
    {
        register_rest_route('wp-wedplan/v1', '/expenses', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_expenses'],
                'permission_callback' => [$this, 'permissions'],
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_expense'],
                'permission_callback' => [$this, 'permissions'],
            ],
        ]);

        register_rest_route('wp-wedplan/v1', '/expenses/(?P<id>\d+)', [
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_expense'],
                'permission_callback' => [$this, 'permissions'],
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_expense'],
                'permission_callback' => [$this, 'permissions'],
            ],
        ]);
    }

    public function permissions(): bool
    {
        return is_user_logged_in();
    }

    public function get_expenses(): WP_REST_Response
    {
        $posts = get_posts([
            'post_type' => 'wedplan_expense',
            'author' => get_current_user_id(),
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ]);

        $data = array_map(function (WP_Post $post): array {
            return [
                'id' => $post->ID,
                'title' => $post->post_title,
                'amount' => (float) get_post_meta($post->ID, 'wedplan_amount', true),
                'status' => get_post_meta($post->ID, 'wedplan_status', true) ?: 'pending',
            ];
        }, $posts);

        return new WP_REST_Response($data, 200);
    }

    public function create_expense(WP_REST_Request $request): WP_REST_Response
    {
        $title = sanitize_text_field((string) $request->get_param('title'));
        $amount = (float) $request->get_param('amount');
        $status = sanitize_key((string) $request->get_param('status'));
        $status = in_array($status, ['paid', 'pending'], true) ? $status : 'pending';

        $post_id = wp_insert_post([
            'post_type' => 'wedplan_expense',
            'post_status' => 'publish',
            'post_title' => $title ?: __('Dépense', 'wp-wedplan'),
            'post_author' => get_current_user_id(),
        ]);

        if (is_wp_error($post_id)) {
            return new WP_REST_Response(['message' => __('Impossible de créer la dépense.', 'wp-wedplan')], 400);
        }

        update_post_meta($post_id, 'wedplan_amount', $amount);
        update_post_meta($post_id, 'wedplan_status', $status);

        return new WP_REST_Response(['id' => $post_id], 201);
    }

    public function update_expense(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $post = get_post($id);

        if (! $post || $post->post_type !== 'wedplan_expense' || (int) $post->post_author !== get_current_user_id()) {
            return new WP_REST_Response(['message' => __('Dépense introuvable.', 'wp-wedplan')], 404);
        }

        $title = $request->get_param('title');
        $amount = $request->get_param('amount');
        $status = $request->get_param('status');

        if ($title !== null) {
            wp_update_post([
                'ID' => $id,
                'post_title' => sanitize_text_field((string) $title),
            ]);
        }

        if ($amount !== null) {
            update_post_meta($id, 'wedplan_amount', (float) $amount);
        }

        if ($status !== null) {
            $safe_status = sanitize_key((string) $status);
            update_post_meta($id, 'wedplan_status', in_array($safe_status, ['paid', 'pending'], true) ? $safe_status : 'pending');
        }

        return new WP_REST_Response(['message' => __('Dépense mise à jour.', 'wp-wedplan')], 200);
    }

    public function delete_expense(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $post = get_post($id);

        if (! $post || $post->post_type !== 'wedplan_expense' || (int) $post->post_author !== get_current_user_id()) {
            return new WP_REST_Response(['message' => __('Dépense introuvable.', 'wp-wedplan')], 404);
        }

        wp_delete_post($id, true);

        return new WP_REST_Response(['message' => __('Dépense supprimée.', 'wp-wedplan')], 200);
    }
}
