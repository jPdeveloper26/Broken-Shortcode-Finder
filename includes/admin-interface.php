<?php
if (!defined('ABSPATH')) exit;

class BSFR_Admin_Interface {
    private $scanner;
    private $repair;
    private $uninstall_guard;
    
    public function __construct($scanner, $repair, $uninstall_guard) {
        $this->scanner = $scanner;
        $this->repair = $repair;
        $this->uninstall_guard = $uninstall_guard;
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_bsfr_scan', array($this, 'ajax_scan'));
        add_action('wp_ajax_bsfr_repair', array($this, 'ajax_repair'));
        add_action('wp_ajax_bsfr_preview', array($this, 'ajax_preview'));
        
        // Only add debug hook if in development environment
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('admin_init', array($this, 'debug_shortcode_detection'));
        }
    }
    
    public function add_admin_menu() {
        add_management_page(
            'Broken Shortcode Finder',
            'Broken Shortcode Finder',
            'manage_options',
            'broken-shortcode-finder',
            array($this, 'render_admin_page')
        );
    }
    
    public function enqueue_scripts($hook) {
        if ($hook !== 'tools_page_broken-shortcode-finder') {
            return;
        }

        wp_enqueue_style(
            'bsfr-admin-css',
            BSFR_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            BSFR_VERSION
        );

        wp_enqueue_script(
            'bsfr-admin-js',
            BSFR_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'underscore'),
            BSFR_VERSION,
            true
        );

        wp_localize_script('bsfr-admin-js', 'bsfr_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bsfr_nonce'),
            'scan_text' => esc_html__('Run Scan', 'broken-shortcode-finder-cp'),
            'scanning_text' => esc_html__('Scanning...', 'broken-shortcode-finder-cp'),
            'confirm_remove' => esc_html__('Are you sure you want to remove all instances of this shortcode?', 'broken-shortcode-finder-cp'),
            'confirm_apply' => esc_html__('Are you sure you want to apply these changes?', 'broken-shortcode-finder-cp'),
            'enter_replacement' => esc_html__('Please enter a replacement shortcode', 'broken-shortcode-finder-cp'),
            'error_text' => esc_html__('An error occurred. Please try again.', 'broken-shortcode-finder-cp'),
            'no_orphans_text' => esc_html__('No orphaned shortcodes found in scanned content.', 'broken-shortcode-finder-cp'),
            'shortcode_not_found_text' => esc_html__('Shortcode not found in selected content.', 'broken-shortcode-finder-cp'),
            'removed_text' => esc_html__('instances of the shortcode were removed.', 'broken-shortcode-finder-cp'),
            'replaced_text' => esc_html__('instances of the shortcode were replaced.', 'broken-shortcode-finder-cp')
        ));
    }

    public function render_admin_page() {
        include BSFR_PLUGIN_DIR . 'templates/admin-dashboard.php';
    }

    public function ajax_scan() {
        check_ajax_referer('bsfr_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $orphans = $this->scanner->find_orphaned_shortcodes(true);
        $stats = $this->scanner->get_shortcode_stats();

        $post_details = array();
        foreach ($orphans as $shortcode => $data) {
            if (!shortcode_exists($shortcode)) {
                $post_details[$shortcode] = $data['posts'];
            }
        }

        wp_send_json_success(array(
            'orphans' => $orphans,
            'stats' => $stats,
            'post_details' => $post_details,
            'message' => empty($post_details) ? __('No orphaned shortcodes found in scanned content.', 'broken-shortcode-finder-cp') : ''
        ));
    }

    public function ajax_repair() {
        check_ajax_referer('bsfr_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        // Sanitize inputs
        $action = isset($_POST['action_type']) ? sanitize_text_field(wp_unslash($_POST['action_type'])) : '';
        $shortcode = isset($_POST['shortcode']) ? sanitize_text_field(wp_unslash($_POST['shortcode'])) : '';
        $replacement = isset($_POST['replacement']) ? sanitize_text_field(wp_unslash($_POST['replacement'])) : '';

        $create_backup_raw = isset($_POST['create_backup']) ? sanitize_text_field(wp_unslash($_POST['create_backup'])) : '';
        $create_backup = ($create_backup_raw === 'true');

        try {
            if (!$this->scanner->is_shortcode_orphaned($shortcode)) {
                throw new Exception(__('Shortcode not found in selected content.', 'broken-shortcode-finder-cp'));
            }

            $result = 0;

            if ($action === 'remove') {
                $result = $this->repair->remove_shortcode($shortcode, $create_backup);
            } elseif ($action === 'replace') {
                if (empty($replacement)) {
                    throw new Exception(__('Please enter a replacement shortcode', 'broken-shortcode-finder-cp'));
                }
                $result = $this->repair->replace_shortcode($shortcode, $replacement, $create_backup);
            } else {
                throw new Exception('Invalid action type');
            }

            wp_send_json_success(array(
                'count' => $result,
                'message' => $result > 0
                    ? ($action === 'remove'
                        /* translators: %d: number of shortcode instances removed */
                        ? sprintf(_n('%d instance of the shortcode was removed.', '%d instances of the shortcode were removed.', $result, 'broken-shortcode-finder-cp'), $result)
                        /* translators: %d: number of shortcode instances replaced */
                        : sprintf(_n('%d instance of the shortcode was replaced.', '%d instances of the shortcode were replaced.', $result, 'broken-shortcode-finder-cp'), $result))
                    : __('Shortcode not found in selected content.', 'broken-shortcode-finder-cp')
            ));
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_preview() {
        check_ajax_referer('bsfr_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $shortcode = isset($_POST['shortcode']) ? sanitize_text_field(wp_unslash($_POST['shortcode'])) : '';
        $replacement = isset($_POST['replacement']) ? sanitize_text_field(wp_unslash($_POST['replacement'])) : '';

        $preview = $this->repair->preview_shortcode_replacement($post_id, $shortcode, $replacement);

        if (is_wp_error($preview)) {
            wp_send_json_error($preview->get_error_message());
        } else {
            wp_send_json_success($preview);
        }
    }

    /**
     * Debug function to test shortcode detection
     * Only available in debug mode
     */
    public function debug_shortcode_detection() {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        if (!isset($_GET['debug_shortcode_scanner']) || !current_user_can('manage_options')) {
            return;
        }

        if (!isset($_GET['_wpnonce'])) {
            wp_die(esc_html__('Security check failed', 'broken-shortcode-finder-cp'));
        }

        $nonce = sanitize_text_field(wp_unslash($_GET['_wpnonce']));
        if (!wp_verify_nonce($nonce, 'bsfr_debug_nonce')) {
            wp_die(esc_html__('Security check failed', 'broken-shortcode-finder-cp'));
        }

        $test_content = '[testcorde] [testcorde2] [testcode3] [youraddress] [Mycolytes]';
        $found = $this->scanner->debug_shortcode_detection($test_content);

        echo '<div class="wrap"><h1>' . esc_html__('Shortcode Scanner Debug', 'broken-shortcode-finder-cp') . '</h1>';
        echo '<h3>' . esc_html__('Test Content:', 'broken-shortcode-finder-cp') . '</h3><pre>' . esc_html($test_content) . '</pre>';
        echo '<h3>' . esc_html__('Detected Shortcodes:', 'broken-shortcode-finder-cp') . '</h3><pre>' . esc_html(wp_json_encode($found, JSON_PRETTY_PRINT)) . '</pre>';

        $orphans = $this->scanner->find_orphaned_shortcodes(true);
        echo '<h3>' . esc_html__('All Orphaned Shortcodes:', 'broken-shortcode-finder-cp') . '</h3><pre>' . esc_html(wp_json_encode(array_keys($orphans), JSON_PRETTY_PRINT)) . '</pre>';

        echo '</div>';
        wp_die();
    }
}