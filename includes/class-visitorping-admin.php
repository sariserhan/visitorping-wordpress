<?php
/**
 * Admin Class for VisitorPing
 *
 * Handles admin menu, settings, verification, and dashboard widgets.
 */

if (!defined('ABSPATH')) {
    exit;
}

class VisitorPing_Admin {

    /**
     * Hook into WordPress admin lifecycle
     */
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_notices', array($this, 'render_admin_notices'));
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        add_action('wp_ajax_visitorping_test_ping', array($this, 'ajax_test_ping'));
        add_action('admin_post_visitorping_rotate_publish_secret', array($this, 'rotate_publish_secret'));
    }

    /**
     * Add settings page under Settings menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('VisitorPing Settings', 'visitorping'),
            __('VisitorPing', 'visitorping'),
            'manage_options',
            'visitorping',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register settings and fields
     */
    public function register_settings() {
        register_setting('visitorping_settings_group', 'visitorping_settings', array(
            'sanitize_callback' => array($this, 'sanitize_settings'),
        ));
    }

    /**
     * Sanitize settings on save
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        $existing = get_option('visitorping_settings', array());

        if (isset($input['site_key'])) {
            $site_key = sanitize_text_field(trim($input['site_key']));
            if ($site_key !== '' && !preg_match('/^vp_[A-HJ-NP-Z2-9]{8}$/', $site_key)) {
                add_settings_error('visitorping_settings', 'invalid_site_key', __('That Site Key is not valid. Copy it exactly from Dashboard → Websites.', 'visitorping'));
                $site_key = '';
            }
            $sanitized['site_key'] = $site_key;
        }

        $sanitized['exclude_admins'] = !empty($input['exclude_admins']) ? 1 : 0;
        $sanitized['exclude_all_users'] = !empty($input['exclude_all_users']) ? 1 : 0;

        if (!empty($input['cdn_url'])) {
            $sanitized['cdn_url'] = esc_url_raw(trim($input['cdn_url']));
        } else {
            $sanitized['cdn_url'] = VISITORPING_DEFAULT_CDN_URL;
        }

        $sanitized['publishing_enabled'] = !empty($input['publishing_enabled']) ? 1 : 0;
        $sanitized['scheduled_publishing_enabled'] = ($sanitized['publishing_enabled'] && !empty($input['scheduled_publishing_enabled'])) ? 1 : 0;
        $sanitized['publishing_secret'] = isset($existing['publishing_secret']) ? $existing['publishing_secret'] : '';
        $sanitized['publisher_user_id'] = isset($existing['publisher_user_id']) ? (int) $existing['publisher_user_id'] : 0;
        if ($sanitized['publishing_enabled'] && $sanitized['publishing_secret'] === '') {
            $sanitized['publishing_secret'] = $this->generate_publish_secret();
            $sanitized['publisher_user_id'] = get_current_user_id();
        }

        return $sanitized;
    }

    private function generate_publish_secret() {
        return rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
    }

    public function rotate_publish_secret() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized', 'visitorping'));
        }
        check_admin_referer('visitorping_rotate_publish_secret');
        $options = get_option('visitorping_settings', array());
        $options['publishing_secret'] = $this->generate_publish_secret();
        $options['publisher_user_id'] = get_current_user_id();
        update_option('visitorping_settings', $options);
        wp_safe_redirect(add_query_arg(array('page' => 'visitorping', 'visitorping_secret_rotated' => '1'), admin_url('options-general.php')));
        exit;
    }

    /**
     * Enqueue styles and scripts for VisitorPing admin page
     */
    public function enqueue_admin_assets($hook) {
        if ($hook !== 'settings_page_visitorping') {
            return;
        }

        wp_enqueue_style(
            'visitorping-admin-css',
            VISITORPING_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            VISITORPING_VERSION
        );

        wp_enqueue_script(
            'visitorping-admin-js',
            VISITORPING_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            VISITORPING_VERSION,
            true
        );

        wp_localize_script('visitorping-admin-js', 'visitorpingAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('visitorping_test_nonce'),
        ));
    }

    /**
     * Show notice if plugin is active but site key is missing
     */
    public function render_admin_notices() {
        $screen = get_current_screen();
        if ($screen && $screen->id === 'settings_page_visitorping') {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $options = get_option('visitorping_settings', array());
        $site_key = isset($options['site_key']) ? trim($options['site_key']) : '';

        if (empty($site_key)) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>' . esc_html__('VisitorPing is installed but not connected.', 'visitorping') . '</strong> ';
            echo '<a href="' . esc_url(admin_url('options-general.php?page=visitorping')) . '">' . esc_html__('Enter your Site Key to start receiving visitor alerts.', 'visitorping') . '</a></p>';
            echo '</div>';
        }
    }

    /**
     * Add quick status dashboard widget
     */
    public function add_dashboard_widget() {
        if (!current_user_can('manage_options')) {
            return;
        }

        wp_add_dashboard_widget(
            'visitorping_dashboard_widget',
            __('VisitorPing — Website Doorbell', 'visitorping'),
            array($this, 'render_dashboard_widget')
        );
    }

    /**
     * Render dashboard widget content
     */
    public function render_dashboard_widget() {
        $options = get_option('visitorping_settings', array());
        $site_key = isset($options['site_key']) ? trim($options['site_key']) : '';
        $is_connected = !empty($site_key);

        echo '<div style="padding: 6px 0;">';
        if ($is_connected) {
            echo '<p style="display:flex;align-items:center;gap:8px;font-size:14px;color:#166534;">';
            echo '<span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#22c55e;"></span>';
            echo '<strong>' . esc_html__('Site key saved', 'visitorping') . '</strong>';
            echo '</p>';
            echo '<p style="color:#64748b;font-size:13px;">' . esc_html__('Open your website in a private window and confirm a visit in the dashboard. Enable phone notifications separately.', 'visitorping') . '</p>';
            echo '<div style="margin-top:12px;">';
            echo '<a href="https://visitorping.com" target="_blank" rel="noopener" class="button button-primary">' . esc_html__('Open Live Radar Dashboard →', 'visitorping') . '</a>';
            echo '</div>';
        } else {
            echo '<p style="color:#b45309;font-size:13px;">' . esc_html__('VisitorPing needs your Site Key before it can start sending visitor notifications.', 'visitorping') . '</p>';
            echo '<a href="' . esc_url(admin_url('options-general.php?page=visitorping')) . '" class="button button-primary">' . esc_html__('Connect Site Key', 'visitorping') . '</a>';
        }
        echo '</div>';
    }

    /**
     * AJAX handler for testing the ping connection
     */
    public function ajax_test_ping() {
        check_ajax_referer('visitorping_test_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'visitorping')));
        }

        $options = get_option('visitorping_settings', array());
        $site_key = isset($options['site_key']) ? trim($options['site_key']) : '';

        if (empty($site_key)) {
            wp_send_json_error(array('message' => __('Please enter and save a Site Key first.', 'visitorping')));
        }

        $cdn_url = !empty($options['cdn_url']) ? rtrim($options['cdn_url'], '/') : VISITORPING_DEFAULT_CDN_URL;
        $tracker_url = $cdn_url . '/' . rawurlencode($site_key) . '.js';

        // Verify CDN script reachability
        $response = wp_remote_get($tracker_url, array('timeout' => 5));
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => __('Could not reach your VisitorPing site script.', 'visitorping')));
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code >= 200 && $code < 400) {
            wp_send_json_success(array('message' => __('The tracking script is reachable. Confirm a test visit in your VisitorPing dashboard.', 'visitorping')));
        } else {
            /* translators: %d: HTTP response status code from the tracking script request. */
            wp_send_json_error(array('message' => sprintf(__('CDN script returned status code %d', 'visitorping'), $code)));
        }
    }

    /**
     * Render the main admin settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $options = get_option('visitorping_settings', array(
            'site_key'          => '',
            'exclude_admins'    => 1,
            'exclude_all_users' => 0,
            'cdn_url'           => VISITORPING_DEFAULT_CDN_URL,
            'publishing_enabled' => 0,
            'scheduled_publishing_enabled' => 0,
            'publishing_secret' => '',
            'publisher_user_id' => 0,
        ));

        $site_key = isset($options['site_key']) ? $options['site_key'] : '';
        $exclude_admins = isset($options['exclude_admins']) ? (bool) $options['exclude_admins'] : true;
        $exclude_all_users = isset($options['exclude_all_users']) ? (bool) $options['exclude_all_users'] : false;
        $cdn_url = !empty($options['cdn_url']) ? $options['cdn_url'] : VISITORPING_DEFAULT_CDN_URL;
        $publishing_enabled = !empty($options['publishing_enabled']);
        $scheduled_publishing_enabled = !empty($options['scheduled_publishing_enabled']);
        $publishing_secret = isset($options['publishing_secret']) ? $options['publishing_secret'] : '';
        $is_connected = !empty($site_key);

        ?>
        <div class="wrap visitorping-admin-wrap">
            <div class="visitorping-header">
                <div class="visitorping-branding">
                    <span class="visitorping-icon">🔔</span>
                    <div>
                        <h1>VisitorPing</h1>
                        <p class="visitorping-tagline"><?php esc_html_e('The doorbell for your website — Real-time visitor notifications.', 'visitorping'); ?></p>
                    </div>
                </div>
                <div class="visitorping-header-actions">
                    <a href="https://visitorping.com" target="_blank" rel="noopener" class="button button-primary">
                        <?php esc_html_e('Open Live Dashboard ↗', 'visitorping'); ?>
                    </a>
                </div>
            </div>

            <!-- Status Banner -->
            <div class="visitorping-status-banner <?php echo $is_connected ? 'connected' : 'disconnected'; ?>">
                <div class="status-indicator"></div>
                <div class="status-content">
                    <h3>
                        <?php if ($is_connected) : ?>
                            <?php esc_html_e('Site key saved', 'visitorping'); ?>
                        <?php else : ?>
                            <?php esc_html_e('Setup Required — Connect Your Site Key', 'visitorping'); ?>
                        <?php endif; ?>
                    </h3>
                    <p>
                        <?php if ($is_connected) : ?>
                            <?php esc_html_e('Your site key is saved. Test the script, then open your site in a private window and confirm a visit in the dashboard.', 'visitorping'); ?>
                        <?php else : ?>
                            <?php esc_html_e('Paste your Site Key below to connect your website to your VisitorPing account.', 'visitorping'); ?>
                        <?php endif; ?>
                    </p>
                </div>
                <?php if ($is_connected) : ?>
                    <button type="button" id="visitorping-test-btn" class="button button-secondary">
                        <?php esc_html_e('Verify Script Connection', 'visitorping'); ?>
                    </button>
                <?php endif; ?>
            </div>
            <div id="visitorping-test-result" style="display:none;" class="notice"></div>

            <div class="visitorping-layout">
                <!-- Main Form Column -->
                <div class="visitorping-main">
                    <form method="post" action="options.php" class="visitorping-card">
                        <?php
                        settings_fields('visitorping_settings_group');
                        ?>
                        
                        <h2 class="card-title"><?php esc_html_e('Connection Settings', 'visitorping'); ?></h2>

                        <div class="form-group">
                            <label for="visitorping_site_key">
                                <strong><?php esc_html_e('Site Key', 'visitorping'); ?></strong>
                                <span class="required">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="visitorping_site_key" 
                                name="visitorping_settings[site_key]" 
                                value="<?php echo esc_attr($site_key); ?>" 
                                class="regular-text code-input"
                                placeholder="vp_ABC23456"
                                required
                            />
                            <p class="description">
                                <?php esc_html_e('Find your Site Key in your', 'visitorping'); ?>
                                <a href="https://visitorping.com/dashboard/sites" target="_blank" rel="noopener">
                                    <?php esc_html_e('VisitorPing Site Settings ↗', 'visitorping'); ?>
                                </a>.
                            </p>
                        </div>

                        <hr class="card-divider" />

                        <h2 class="card-title"><?php esc_html_e('Draft Publishing', 'visitorping'); ?></h2>

                        <div class="form-checkbox-group">
                            <label>
                                <input
                                    type="checkbox"
                                    name="visitorping_settings[publishing_enabled]"
                                    value="1"
                                    <?php checked($publishing_enabled, true); ?>
                                />
                                <strong><?php esc_html_e('Allow VisitorPing to create WordPress drafts', 'visitorping'); ?></strong>
                            </label>
                            <p class="description">
                                <?php esc_html_e('Drafts land in your Posts list for review. This integration can never update or delete existing posts.', 'visitorping'); ?>
                            </p>
                        </div>

                        <div class="form-checkbox-group">
                            <label>
                                <input
                                    type="checkbox"
                                    name="visitorping_settings[scheduled_publishing_enabled]"
                                    value="1"
                                    <?php checked($scheduled_publishing_enabled, true); ?>
                                />
                                <strong><?php esc_html_e('Also allow VisitorPing to publish scheduled daily articles', 'visitorping'); ?></strong>
                            </label>
                            <p class="description">
                                <?php esc_html_e('Leave this off to keep every article as a draft you approve by hand. Turning it on lets one scheduled article per day go live on this site without review.', 'visitorping'); ?>
                            </p>
                        </div>

                        <?php if ($publishing_secret !== '') : ?>
                            <div class="form-group">
                                <label for="visitorping_publishing_secret"><strong><?php esc_html_e('Publishing secret', 'visitorping'); ?></strong></label>
                                <input
                                    type="text"
                                    id="visitorping_publishing_secret"
                                    value="<?php echo esc_attr($publishing_secret); ?>"
                                    class="large-text code-input"
                                    readonly
                                    autocomplete="off"
                                    onclick="this.select();"
                                />
                                <p class="description"><?php esc_html_e('Copy this once into VisitorPing → SEO → Content Growth. Treat it like a password.', 'visitorping'); ?></p>
                            </div>
                        <?php else : ?>
                            <p class="description"><?php esc_html_e('Save settings after enabling draft publishing to generate your secret.', 'visitorping'); ?></p>
                        <?php endif; ?>

                        <hr class="card-divider" />

                        <h2 class="card-title"><?php esc_html_e('Tracking Rules & Privacy', 'visitorping'); ?></h2>

                        <div class="form-checkbox-group">
                            <label>
                                <input 
                                    type="checkbox" 
                                    name="visitorping_settings[exclude_admins]" 
                                    value="1" 
                                    <?php checked($exclude_admins, true); ?>
                                />
                                <strong><?php esc_html_e('Exclude Website Administrators & Editors', 'visitorping'); ?></strong>
                            </label>
                            <p class="description">
                                <?php esc_html_e('Prevents triggering notifications on your phone when you or your team are logged into WordPress editing content.', 'visitorping'); ?>
                            </p>
                        </div>

                        <div class="form-checkbox-group">
                            <label>
                                <input 
                                    type="checkbox" 
                                    name="visitorping_settings[exclude_all_users]" 
                                    value="1" 
                                    <?php checked($exclude_all_users, true); ?>
                                />
                                <strong><?php esc_html_e('Exclude All Logged-in WordPress Users', 'visitorping'); ?></strong>
                            </label>
                            <p class="description">
                                <?php esc_html_e('If your website has customer accounts (e.g. WooCommerce), enable this to only track anonymous public visitors.', 'visitorping'); ?>
                            </p>
                        </div>

                        <div class="advanced-section">
                            <details>
                                <summary><?php esc_html_e('Advanced Configuration', 'visitorping'); ?></summary>
                                <div class="form-group" style="margin-top: 12px;">
                                    <label for="visitorping_cdn_url"><?php esc_html_e('Custom CDN Site Script Base URL', 'visitorping'); ?></label>
                                    <input 
                                        type="url" 
                                        id="visitorping_cdn_url" 
                                        name="visitorping_settings[cdn_url]" 
                                        value="<?php echo esc_attr($cdn_url); ?>" 
                                        class="regular-text"
                                    />
                                    <p class="description"><?php esc_html_e('Default is https://cdn.visitorping.com/site. Leave as default unless using a custom proxy domain.', 'visitorping'); ?></p>
                                </div>
                            </details>
                        </div>

                        <div class="card-footer">
                            <?php submit_button(__('Save Settings', 'visitorping'), 'primary', 'submit', false); ?>
                        </div>
                    </form>
                    <?php if ($publishing_secret !== '') : ?>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="visitorping-card">
                            <input type="hidden" name="action" value="visitorping_rotate_publish_secret" />
                            <?php wp_nonce_field('visitorping_rotate_publish_secret'); ?>
                            <h2 class="card-title"><?php esc_html_e('Publishing secret security', 'visitorping'); ?></h2>
                            <p class="description"><?php esc_html_e('Rotate the secret if it was exposed. VisitorPing will need to be paired again.', 'visitorping'); ?></p>
                            <?php submit_button(__('Rotate Publishing Secret', 'visitorping'), 'secondary', 'submit', false); ?>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Sidebar Guide Column -->
                <div class="visitorping-sidebar">
                    <div class="visitorping-card sidebar-card">
                        <h3><?php esc_html_e('How it works', 'visitorping'); ?></h3>
                        <ol class="steps-list">
                            <li><strong><?php esc_html_e('Install Plugin', 'visitorping'); ?></strong>: <?php esc_html_e('Automatically places the fast, lightweight tracking script on your site.', 'visitorping'); ?></li>
                            <li><strong><?php esc_html_e('Get the Mobile App', 'visitorping'); ?></strong>: <?php esc_html_e('Install VisitorPing on your iPhone from the mobile download page.', 'visitorping'); ?></li>
                            <li><strong><?php esc_html_e('Receive Alerts', 'visitorping'); ?></strong>: <?php esc_html_e('Enable arrival alerts in the app. A visit does not necessarily mean a prospective customer.', 'visitorping'); ?></li>
                        </ol>
                    </div>

                    <div class="visitorping-card sidebar-card">
                        <h3><?php esc_html_e('Download Mobile App', 'visitorping'); ?></h3>
                        <p class="description"><?php esc_html_e('Log into the app with the same VisitorPing account to connect your phone for push notifications.', 'visitorping'); ?></p>
                        <div class="app-links">
                            <a href="https://visitorping.com/download" target="_blank" rel="noopener" class="app-badge">
                                🍏 <?php esc_html_e('Download on App Store', 'visitorping'); ?>
                            </a>

                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
