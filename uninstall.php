<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

$table_name = $wpdb->prefix . 'edpc_login';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

$option_keys = [
    'edpc_auth_token',
    'edpc_token_time',
    'edpc_api_mode',
    'edpc_pickup_pincode',
    'edpc_button_bg',
    'edpc_button_hover_bg',
    'edpc_icon_url',
    'edpc_icon_width',
    'edpc_icon_height',
];

foreach ($option_keys as $key) {
    delete_option($key);
    delete_site_option($key);
}
