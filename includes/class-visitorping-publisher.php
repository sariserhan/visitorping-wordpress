<?php
/** Secure publishing endpoints for VisitorPing. Drafts always; published posts only when the site owner opts in. */

if (!defined('ABSPATH')) {
    exit;
}

class VisitorPing_Publisher {
    const NAMESPACE = 'visitorping/v1';
    const MAX_CLOCK_SKEW = 300;

    public function init() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes() {
        register_rest_route(self::NAMESPACE, '/verify', array(
            'methods' => 'POST',
            'callback' => array($this, 'verify'),
            'permission_callback' => array($this, 'authorize_request'),
        ));
        register_rest_route(self::NAMESPACE, '/drafts', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_draft'),
            'permission_callback' => array($this, 'authorize_request'),
        ));
    }

    public function authorize_request($request) {
        $options = get_option('visitorping_settings', array());
        $secret = isset($options['publishing_secret']) ? (string) $options['publishing_secret'] : '';
        if (empty($options['publishing_enabled']) || $secret === '') {
            return new WP_Error('visitorping_publishing_disabled', __('VisitorPing draft publishing is disabled.', 'visitorping'), array('status' => 403));
        }

        $timestamp = $request->get_header('x-visitorping-timestamp');
        $signature = $request->get_header('x-visitorping-signature');
        if (!ctype_digit((string) $timestamp) || abs(time() - (int) $timestamp) > self::MAX_CLOCK_SKEW) {
            return new WP_Error('visitorping_expired_request', __('The publishing request has expired.', 'visitorping'), array('status' => 401));
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $request->get_body(), $secret);
        if (!is_string($signature) || !hash_equals($expected, $signature)) {
            return new WP_Error('visitorping_invalid_signature', __('The publishing signature is invalid.', 'visitorping'), array('status' => 401));
        }

        return true;
    }

    public function verify() {
        $capabilities = array('create_draft', 'tags');
        if ($this->scheduled_publishing_enabled()) {
            $capabilities[] = 'publish_post';
        }

        return rest_ensure_response(array(
            'siteName' => get_bloginfo('name'),
            'siteUrl' => home_url('/'),
            'pluginVersion' => VISITORPING_VERSION,
            'capabilities' => $capabilities,
        ));
    }

    private function scheduled_publishing_enabled() {
        $options = get_option('visitorping_settings', array());
        return !empty($options['publishing_enabled']) && !empty($options['scheduled_publishing_enabled']);
    }

    public function create_draft($request) {
        $idempotency_key = sanitize_text_field($request->get_header('x-visitorping-idempotency-key'));
        if ($idempotency_key === '') {
            return new WP_Error('visitorping_missing_idempotency_key', __('An idempotency key is required.', 'visitorping'), array('status' => 400));
        }
        $idempotency_hash = hash('sha256', $idempotency_key);
        $existing = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'any',
            'meta_key' => '_visitorping_idempotency_key',
            'meta_value' => $idempotency_hash,
            'numberposts' => 1,
            'fields' => 'ids',
        ));
        if (!empty($existing)) {
            return rest_ensure_response($this->post_response((int) $existing[0]));
        }

        $options = get_option('visitorping_settings', array());
        $author_id = isset($options['publisher_user_id']) ? (int) $options['publisher_user_id'] : 0;
        if ($author_id < 1 || !user_can($author_id, 'edit_posts')) {
            return new WP_Error('visitorping_invalid_author', __('The configured WordPress author can no longer create drafts.', 'visitorping'), array('status' => 403));
        }

        $payload = $request->get_json_params();
        $title = isset($payload['title']) ? sanitize_text_field($payload['title']) : '';
        $content = isset($payload['contentHtml']) ? wp_kses_post($payload['contentHtml']) : '';
        if ($title === '' || $content === '') {
            return new WP_Error('visitorping_invalid_draft', __('A title and article body are required.', 'visitorping'), array('status' => 400));
        }

        $requested_status = isset($payload['requestedStatus']) ? sanitize_key($payload['requestedStatus']) : 'draft';
        if (!in_array($requested_status, array('draft', 'publish'), true)) {
            return new WP_Error('visitorping_invalid_status', __('Only draft and publish statuses are supported.', 'visitorping'), array('status' => 400));
        }
        if ($requested_status === 'publish' && !$this->scheduled_publishing_enabled()) {
            return new WP_Error('visitorping_publishing_not_allowed', __('Scheduled publishing is turned off for this site.', 'visitorping'), array('status' => 403));
        }
        if ($requested_status === 'publish' && !user_can($author_id, 'publish_posts')) {
            return new WP_Error('visitorping_invalid_author', __('The configured WordPress author cannot publish posts.', 'visitorping'), array('status' => 403));
        }

        $post_id = wp_insert_post(array(
            'post_type' => 'post',
            'post_status' => $requested_status,
            'post_author' => $author_id,
            'post_title' => $title,
            'post_name' => isset($payload['slug']) ? sanitize_title($payload['slug']) : '',
            'post_excerpt' => isset($payload['excerpt']) ? sanitize_textarea_field($payload['excerpt']) : '',
            'post_content' => $content,
        ), true);
        if (is_wp_error($post_id)) {
            return $post_id;
        }

        update_post_meta($post_id, '_visitorping_idempotency_key', $idempotency_hash);
        if (!empty($payload['sourceDraftId'])) {
            update_post_meta($post_id, '_visitorping_source_draft_id', sanitize_text_field($payload['sourceDraftId']));
        }
        if (!empty($payload['tags']) && is_array($payload['tags'])) {
            $tags = array_slice(array_values(array_filter(array_map('sanitize_text_field', $payload['tags']))), 0, 20);
            wp_set_post_tags($post_id, $tags, false);
        }

        return new WP_REST_Response($this->post_response($post_id), 201);
    }

    private function post_response($post_id) {
        return array(
            'id' => (string) $post_id,
            'status' => get_post_status($post_id),
            'editUrl' => get_edit_post_link($post_id, 'raw'),
            'previewUrl' => get_preview_post_link($post_id),
        );
    }
}
