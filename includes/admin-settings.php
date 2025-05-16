<?php
add_action('admin_menu', function () {
    add_menu_page('EDPC Settings', 'EDPC Settings', 'manage_options', 'edpc-settings', 'edpc_settings_page');
});

function edpc_settings_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'edpc_login';
    $data = $wpdb->get_row("SELECT * FROM $table WHERE id = 1");

    $button_bg = get_option('edpc_button_bg', '#9de7c1');
    $button_hover_bg = get_option('edpc_button_hover_bg', '#7edba8');
    $icon_url = get_option('edpc_icon_url', plugin_dir_url(__FILE__) . 'assets/location.png');
    $icon_width = get_option('edpc_icon_width', '24');
    $icon_height = get_option('edpc_icon_height', '24');
    $pickup_pincode = get_option('edpc_pickup_pincode', '');
    $api_mode = get_option('edpc_api_mode', 'pincode');

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('edpc_save_settings')) {
        $email = sanitize_email($_POST['edpc_email']);
        $password = sanitize_text_field($_POST['edpc_password']);
        $button_bg = sanitize_hex_color($_POST['edpc_button_bg']);
        $button_hover_bg = sanitize_hex_color($_POST['edpc_button_hover_bg']);
        $icon_url = esc_url_raw($_POST['edpc_icon_url']);
        $icon_width = absint($_POST['edpc_icon_width']);
        $icon_height = absint($_POST['edpc_icon_height']);
        $pickup_pincode = sanitize_text_field($_POST['edpc_pickup_pincode']);
        $api_mode = in_array($_POST['edpc_api_mode'], ['pincode', 'estimate']) ? $_POST['edpc_api_mode'] : 'pincode';

        $wpdb->update($table, ['email' => $email, 'password' => $password], ['id' => 1]);

        update_option('edpc_button_bg', $button_bg);
        update_option('edpc_button_hover_bg', $button_hover_bg);
        update_option('edpc_icon_url', $icon_url);
        update_option('edpc_icon_width', $icon_width);
        update_option('edpc_icon_height', $icon_height);
        update_option('edpc_pickup_pincode', $pickup_pincode);
        update_option('edpc_api_mode', $api_mode);

        echo '<div class="updated"><p>Settings saved.</p></div>';
        $data = $wpdb->get_row("SELECT * FROM $table WHERE id = 1");
    }
?>
    <div class="wrap">
        <h2>Shiprocket API Credentials & Styling</h2>
        <p>Use shortcode: <code>[delivery_pincode_form]</code></p>
        <form method="post">
            <?php wp_nonce_field('edpc_save_settings'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="edpc_email">Email</label></th>
                    <td><input name="edpc_email" type="email" value="<?php echo esc_attr($data->email); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="edpc_password">Password</label></th>
                    <td><input name="edpc_password" type="password" value="<?php echo esc_attr($data->password); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="edpc_pickup_pincode">Pickup Pincode</label></th>
                    <td><input name="edpc_pickup_pincode" type="text" value="<?php echo esc_attr($pickup_pincode); ?>" class="regular-text" maxlength="6" pattern="\d{6}"></td>
                </tr>
                <tr>
                    <th><label for="edpc_api_mode">API Mode</label></th>
                    <td>
                        <select name="edpc_api_mode">
                            <option value="pincode" <?php selected($api_mode, 'pincode'); ?>>Pincode Availability Check</option>
                            <option value="estimate" <?php selected($api_mode, 'estimate'); ?>>Estimated Delivery Date</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="edpc_button_bg">Button Background Color</label></th>
                    <td><input name="edpc_button_bg" type="color" value="<?php echo esc_attr($button_bg); ?>"></td>
                </tr>
                <tr>
                    <th><label for="edpc_button_hover_bg">Button Hover Color</label></th>
                    <td><input name="edpc_button_hover_bg" type="color" value="<?php echo esc_attr($button_hover_bg); ?>"></td>
                </tr>
                <tr>
                    <th><label for="edpc_icon_url">Location Icon</label></th>
                    <td>
                        <img id="icon-preview" src="<?php echo esc_url($icon_url); ?>" style="max-width: 100px; display: block; margin-bottom: 10px; width: <?php echo $icon_width; ?>px; height: <?php echo $icon_height; ?>px;">
                        <input type="hidden" name="edpc_icon_url" id="edpc_icon_url" value="<?php echo esc_url($icon_url); ?>">
                        <button type="button" class="button" id="upload_icon_button">Upload/Select Icon</button>
                        <button type="button" class="button" id="remove_icon_button">Remove</button>
                    </td>
                </tr>
                <tr>
                    <th><label for="edpc_icon_width">Icon Width (px)</label></th>
                    <td><input name="edpc_icon_width" type="number" value="<?php echo esc_attr($icon_width); ?>" min="1" style="width: 80px;"></td>
                </tr>
                <tr>
                    <th><label for="edpc_icon_height">Icon Height (px)</label></th>
                    <td><input name="edpc_icon_height" type="number" value="<?php echo esc_attr($icon_height); ?>" min="1" style="width: 80px;"></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>

    <script>
    jQuery(document).ready(function ($) {
        var mediaUploader;

        $('#upload_icon_button').click(function (e) {
            e.preventDefault();

            if (mediaUploader) {
                mediaUploader.open();
                return;
            }

            mediaUploader = wp.media({
                title: 'Select or Upload Icon',
                button: {
                    text: 'Use this icon'
                },
                multiple: false
            });

            mediaUploader.on('select', function () {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#edpc_icon_url').val(attachment.url);
                $('#icon-preview').attr('src', attachment.url).show();
            });

            mediaUploader.open();
        });

        $('#remove_icon_button').click(function () {
            $('#edpc_icon_url').val('');
            $('#icon-preview').hide();
        });
    });
    </script>
<?php
}

// Enqueue media uploader
add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook === 'toplevel_page_edpc-settings') {
        wp_enqueue_media();
    }
});
