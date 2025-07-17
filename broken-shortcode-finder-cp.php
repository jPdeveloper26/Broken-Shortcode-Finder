<?php
/*
* Plugin Name: Broken Shortcode Finder 
* Plugin URI: https://wpbay.com/store/cognitowp/
* Description: Scans your WordPress site for orphaned shortcodes and helps you repair or remove them.
* Version: 1.0.9
* Author: CognitoWP
 * Author URI: https://wpbay.com/store/cognitowp/
 * License: GPL-2.0+
 * Text Domain: broken-shortcode-finder-cp
 * Domain Path: /languages
 * Requires PHP: 7.4
*/

defined('ABSPATH') or die('No direct access!');

if ( ! function_exists( 'cwpbsf_wpbay_sdk' ) ) {
    function cwpbsf_wpbay_sdk() {
        require_once dirname( __FILE__ ) . '/wpbay-sdk/WPBay_Loader.php';
        $sdk_instance = false;
        global $wpbay_sdk_latest_loader;
        $sdk_loader_class = $wpbay_sdk_latest_loader;
        $sdk_params = array(
            'api_key'                 => 'OIAKDA-LTRHGZK4VP5ZXK3DECZI2OJACI',
            'wpbay_product_id'        => '', 
            'product_file'            => __FILE__,
            'activation_redirect'     => '',
            'is_free'                 => true,
            'is_upgradable'           => false,
            'uploaded_to_wp_org'      => false,
            'disable_feedback'        => false,
            'disable_support_page'    => false,
            'disable_contact_form'    => false,
            'disable_upgrade_form'    => true,
            'disable_analytics'       => false,
            'rating_notice'           => '1 week',
            'debug_mode'              => 'false',
            'no_activation_required'  => false,
            'menu_data'               => array(
                'menu_slug' => ''
            ),
        );
        if ( class_exists( $sdk_loader_class ) ) {
            $sdk_instance = $sdk_loader_class::load_sdk( $sdk_params );
        }
        return $sdk_instance;
    }
    cwpbsf_wpbay_sdk();
    do_action( 'cwpbsf_wpbay_sdk_loaded' );
}



define('BSFR_VERSION', '1.0');
define('BSFR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BSFR_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include all necessary files
require_once BSFR_PLUGIN_DIR . 'includes/class-scanner.php';
require_once BSFR_PLUGIN_DIR . 'includes/class-backup-manager.php';
require_once BSFR_PLUGIN_DIR . 'includes/class-repair.php';
require_once BSFR_PLUGIN_DIR . 'includes/admin-interface.php';
require_once BSFR_PLUGIN_DIR . 'includes/uninstall-guard.php';

function bsfr_init() {
    $scanner = new BSFR_Shortcode_Scanner();
    $repair = new BSFR_Shortcode_Repair();
    $uninstall_guard = new BSFR_Uninstall_Guard();
    
    if (is_admin()) {
        $admin_interface = new BSFR_Admin_Interface($scanner, $repair, $uninstall_guard);
    }
}
add_action('plugins_loaded', 'bsfr_init');

register_activation_hook(__FILE__, 'bsfr_activate');
function bsfr_activate() {
    if (!get_option('bsfr_shortcode_map')) {
        update_option('bsfr_shortcode_map', array());
    }
}

register_deactivation_hook(__FILE__, 'bsfr_deactivate');
function bsfr_deactivate() {
    // Clean up if needed
}