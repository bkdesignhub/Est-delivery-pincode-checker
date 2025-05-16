<?php
/**
 * Plugin Name: Estimate Delivery & Pincode Checker
 * Description: Check delivery availability and estimated delivery date using the Shiprocket API via shortcode.
 * Version: 1.1
 * Author: Bharath
 */

if (!defined('ABSPATH')) exit;

define('EDPC_DIR', plugin_dir_path(__FILE__));
define('EDPC_URL', plugin_dir_url(__FILE__));

require_once EDPC_DIR . 'includes/ajax-handler.php';
require_once EDPC_DIR . 'includes/admin-settings.php';

function edpc_enqueue_scripts() {
    wp_enqueue_script('jquery');
    wp_enqueue_style('edpc-style', EDPC_URL . 'css/style.css');
    wp_enqueue_script('edpc-script', EDPC_URL . 'js/pincode-check.js', ['jquery'], null, true);
    wp_localize_script('edpc-script', 'edpc_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('edpc_nonce'),
    ]);
}
add_action('wp_enqueue_scripts', 'edpc_enqueue_scripts');

function edpc_form_shortcode() {
    $button_bg = get_option('edpc_button_bg', '#9de7c1');
    $button_hover_bg = get_option('edpc_button_hover_bg', '#7edba8');
    $icon_url = get_option('edpc_icon_url', plugin_dir_url(__FILE__) . 'assets/location.png');
    $icon_width = get_option('edpc_icon_width', '24');
    $icon_height = get_option('edpc_icon_height', '24');

    ob_start();
    ?>
    <style>
    #check-pincode {
        background: <?php echo esc_attr($button_bg); ?>;
        border: none;
        color: white;
        padding: 8px 16px;
        cursor: pointer;
    }
    #check-pincode:hover {
        background: <?php echo esc_attr($button_hover_bg); ?>;
    }
    </style>

    <div class="shiprocket-check-container">
        <div class="shiprocket-input-wrapper">
            <img src="<?php echo esc_url($icon_url); ?>" class="shiprocket-icon" alt="Location Icon"
     style="width: <?php echo esc_attr($icon_width); ?>px; height: <?php echo esc_attr($icon_height); ?>px;">

            <input type="text" id="pincode" placeholder="Enter pincode" maxlength="6" pattern="\d{6}">
            <button type="button" id="check-pincode">CHECK</button>
        </div>
        <div id="pincode-result" class="shiprocket-result-message" style="display: none;"></div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('delivery_pincode_form', 'edpc_form_shortcode');

function edpc_plugin_activate() {
    global $wpdb;
    $table = $wpdb->prefix . 'edpc_login';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id INT NOT NULL AUTO_INCREMENT,
        email VARCHAR(255) NOT NULL,
        password VARCHAR(255) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    // Insert default empty row if none exists
    if (!$wpdb->get_var("SELECT COUNT(*) FROM $table WHERE id = 1")) {
        $wpdb->insert($table, ['email' => '', 'password' => '']);
    }
    // Set default style/image options
    add_option('edpc_button_bg', '#9de7c1');
    add_option('edpc_button_hover_bg', '#7edba8');
    add_option('edpc_icon_url', plugin_dir_url(__FILE__) . 'assets/location.png');
    add_option('edpc_icon_width', '24');
    add_option('edpc_icon_height', '24');
}
register_activation_hook(__FILE__, 'edpc_plugin_activate');
