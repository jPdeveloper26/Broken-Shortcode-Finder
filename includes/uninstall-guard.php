<?php
class BSFR_Uninstall_Guard {
    private $shortcode_map = array();
    
    public function __construct() {
        add_action('admin_init', array($this, 'check_plugin_uninstall'));
        add_action('activated_plugin', array($this, 'track_plugin_activation'));
        add_action('deactivated_plugin', array($this, 'track_plugin_deactivation'));
        add_action('registered_shortcode', array($this, 'track_shortcode_registration'), 10, 2);
        add_action('admin_footer-plugins.php', array($this, 'print_delete_plugins_nonce'));

        $this->load_shortcode_map();
    }
    
    private function load_shortcode_map() {
        $this->shortcode_map = get_option('bsfr_shortcode_map', array());
    }
    
    private function save_shortcode_map() {
        update_option('bsfr_shortcode_map', $this->shortcode_map);
    }
    
    public function track_plugin_activation($plugin) {
        $this->update_plugin_shortcodes($plugin);
    }
    
    public function track_plugin_deactivation($plugin) {
        // Keep tracking even when deactivated
    }
    
    public function track_shortcode_registration($shortcode, $callback) {
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $plugin = $this->find_plugin_from_backtrace($backtrace);
        
        if ($plugin) {
            if (!isset($this->shortcode_map[$shortcode])) {
                $this->shortcode_map[$shortcode] = array();
            }
            
            if (!in_array($plugin, $this->shortcode_map[$shortcode], true)) {
                $this->shortcode_map[$shortcode][] = $plugin;
                $this->save_shortcode_map();
            }
        }
    }
    
    private function find_plugin_from_backtrace($backtrace) {
        foreach ($backtrace as $trace) {
            if (isset($trace['file'])) {
                $file = wp_normalize_path($trace['file']);
                $plugin_dir = wp_normalize_path(WP_PLUGIN_DIR);
                
                if (strpos($file, $plugin_dir) === 0) {
                    $relative_path = substr($file, strlen($plugin_dir) + 1);
                    $plugin_parts = explode('/', $relative_path);
                    return $plugin_parts[0] . '/' . $plugin_parts[0] . '.php';
                }
            }
        }
        return false;
    }
    
    private function update_plugin_shortcodes($plugin) {
        // Implementation would parse plugin files for add_shortcode calls
    }
    
    public function get_plugin_shortcodes($plugin_file) {
        $plugin_shortcodes = array();
        
        foreach ($this->shortcode_map as $shortcode => $plugins) {
            if (in_array($plugin_file, $plugins, true)) {
                $plugin_shortcodes[] = $shortcode;
            }
        }
        
        return $plugin_shortcodes;
    }
    
    public function check_plugin_uninstall() {
        if (!isset($_GET['action'], $_REQUEST['_wpnonce'], $_REQUEST['checked'])) {
            return;
        }

        $action = sanitize_text_field(wp_unslash($_GET['action']));
        if ('delete-selected' !== $action) {
            return;
        }

        $nonce = sanitize_text_field(wp_unslash($_REQUEST['_wpnonce']));
        if (!wp_verify_nonce($nonce, 'bsfr_delete_plugins')) {
            return;
        }

        $checked = sanitize_text_field(wp_unslash($_REQUEST['checked'])); // Unslash first
        $plugins = array_map('sanitize_text_field', (array) $checked); // Then sanitize

        foreach ($plugins as $plugin) {
            $shortcodes = $this->get_plugin_shortcodes($plugin);
        
            if (!empty($shortcodes)) {
                $this->warn_before_uninstall($plugin, $shortcodes);
            }
        }
    }
    
    private function warn_before_uninstall($plugin_file, $shortcodes) {
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_file);
        
        /* translators: 1: Plugin name, 2: Comma-separated list of shortcodes */
        $warning_message = esc_html__('Warning: The plugin "%1$s" uses these shortcodes: %2$s. Uninstalling will orphan these shortcodes.', 'broken-shortcode-finder-cp');
        $repair_message = esc_html__('Consider using the Shortcode Repair tool first.', 'broken-shortcode-finder-cp');
        
        $message = sprintf(
            $warning_message,
            esc_html($plugin_data['Name']),
            esc_html(implode(', ', $shortcodes))
        ) . ' ' . $repair_message;
        
        add_action('admin_notices', function() use ($message) {
            echo '<div class="notice notice-warning is-dismissible"><p>' . wp_kses_post($message) . '</p></div>';
        });
    }

    public function print_delete_plugins_nonce() {
        // Print nonce hidden input on the Plugins admin page for bulk delete form
        $nonce = wp_create_nonce('bsfr_delete_plugins');
        echo '<input type="hidden" name="_wpnonce" value="' . esc_attr($nonce) . '">';
    }
}