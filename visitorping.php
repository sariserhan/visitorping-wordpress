<?php
/**
 * Plugin Name:       VisitorPing — Real-Time Website Visitor Alerts
 * Plugin URI:        https://visitorping.com
 * Description:       A doorbell for your website. Real-time website visitor notifications and intelligence delivered to your mobile phone.
 * Version:           1.3.2
 * Requires at least: 5.6
 * Requires PHP:      7.4
 * Author:            VisitorPing
 * Author URI:        https://visitorping.com
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       visitorping
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('VISITORPING_VERSION', '1.3.2');
define('VISITORPING_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VISITORPING_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VISITORPING_DEFAULT_CDN_URL', 'https://cdn.visitorping.com/site');

// Include core classes
require_once VISITORPING_PLUGIN_DIR . 'includes/class-visitorping-tracker.php';
require_once VISITORPING_PLUGIN_DIR . 'includes/class-visitorping-admin.php';
require_once VISITORPING_PLUGIN_DIR . 'includes/class-visitorping-publisher.php';

/**
 * Initialize the VisitorPing plugin
 */
function visitorping_init() {
    $tracker = new VisitorPing_Tracker();
    $tracker->init();

    $publisher = new VisitorPing_Publisher();
    $publisher->init();

    if (is_admin()) {
        $admin = new VisitorPing_Admin();
        $admin->init();
    }
}
add_action('plugins_loaded', 'visitorping_init');

/**
 * Add settings action link to the Plugins table
 */
function visitorping_plugin_action_links($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=visitorping') . '">' . __('Settings', 'visitorping') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'visitorping_plugin_action_links');
