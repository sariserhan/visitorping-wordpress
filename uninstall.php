<?php
/**
 * Fired when the VisitorPing plugin is uninstalled.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options from the database
delete_option('visitorping_settings');
