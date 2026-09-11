<?php
/**
 * Tracker Class for VisitorPing
 *
 * Injects the lightweight vp.js script into the site's <head>.
 */

if (!defined('ABSPATH')) {
    exit;
}

class VisitorPing_Tracker {

    /**
     * Hook into WordPress front-end lifecycle
     */
    public function init() {
        add_action('wp_head', array($this, 'render_tracking_script'), 1);
    }

    /**
     * Determines whether the current request should be tracked
     *
     * @return bool
     */
    public function should_track() {
        // Do not track in WP Admin, preview mode, or feed
        if (is_admin() || is_preview() || is_feed() || is_robots() || is_trackback()) {
            return false;
        }

        $options = get_option('visitorping_settings', array());
        $site_key = isset($options['site_key']) ? trim($options['site_key']) : '';

        // Must have a valid site key
        if (empty($site_key) || !preg_match('/^vp_[A-HJ-NP-Z2-9]{8}$/', $site_key)) {
            return false;
        }

        // Exclude logged in admins/editors if enabled
        $exclude_admins = isset($options['exclude_admins']) ? (bool) $options['exclude_admins'] : true;
        if ($exclude_admins && is_user_logged_in() && current_user_can('edit_posts')) {
            return false;
        }

        // Exclude all logged in users if enabled
        $exclude_all_users = isset($options['exclude_all_users']) ? (bool) $options['exclude_all_users'] : false;
        if ($exclude_all_users && is_user_logged_in()) {
            return false;
        }

        /**
         * Filter to allow third-party plugins or themes to control tracking
         */
        return apply_filters('visitorping_should_track', true);
    }

    /**
     * Outputs the <script> tag into wp_head
     */
    public function render_tracking_script() {
        if (!$this->should_track()) {
            return;
        }

        $options = get_option('visitorping_settings', array());
        $site_key = trim($options['site_key']);
        $cdn_url = !empty($options['cdn_url']) ? rtrim(esc_url(trim($options['cdn_url'])), '/') : VISITORPING_DEFAULT_CDN_URL;
        $tracker_url = esc_url($cdn_url . '/' . rawurlencode($site_key) . '.js');

        /*
         * Marking the tag cross-origin is what lets errors thrown by the
         * tracker reach the site owner's error tracking with a real message
         * and stack instead of an opaque "Script error."
         *
         * Applied only to our own CDN, which answers with
         * Access-Control-Allow-Origin. Marking a tag turns its fetch into a
         * CORS request, so on a custom proxy that does not send that header
         * the browser would refuse to run the script and tracking would stop.
         * A silent break in exchange for better diagnostics is a bad trade.
         */
        $crossorigin = ($cdn_url === VISITORPING_DEFAULT_CDN_URL) ? ' crossorigin="anonymous"' : '';

        echo "\n<!-- VisitorPing Website Doorbell Tracker -->\n";
        echo '<script defer' . $crossorigin . ' src="' . $tracker_url . '"></script>' . "\n";
        echo "<!-- /VisitorPing -->\n\n";
    }
}
