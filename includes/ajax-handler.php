<?php
add_action('wp_ajax_check_edpc_pincode', 'check_edpc_pincode_callback');
add_action('wp_ajax_nopriv_check_edpc_pincode', 'check_edpc_pincode_callback');

function check_edpc_pincode_callback() {
    check_ajax_referer('edpc_nonce', 'nonce');

    $pincode = sanitize_text_field($_POST['pincode']);

    if (empty($pincode)) {
        wp_send_json_error(['message' => 'Pincode is required.']);
    }

    if (!preg_match('/^\d{6}$/', $pincode)) {
        wp_send_json_error(['message' => 'Invalid pincode. Must be exactly 6 digits.']);
    }

    global $wpdb;
    $credentials = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}edpc_login WHERE id = 1");

    if (!$credentials) {
        wp_send_json_error(['message' => 'Shiprocket credentials not configured.']);
    }

    // Try to get cached token
    $cached_token = get_option('edpc_auth_token');
    $cached_token_time = get_option('edpc_token_time');

    $should_refresh = true;
    if ($cached_token && $cached_token_time) {
        $age_in_seconds = time() - intval($cached_token_time);
        if ($age_in_seconds < (7 * 24 * 60 * 60)) {
            $should_refresh = false;
        }
    }

    if ($should_refresh) {
        // Login and get new token
        $login_data = [
            'email'    => $credentials->email,
            'password' => $credentials->password,
        ];

        $response = wp_remote_post("https://apiv2.shiprocket.in/v1/external/auth/login", [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => json_encode($login_data),
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'Login request failed.']);
        }

        $token_data = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($token_data['token'])) {
            wp_send_json_error(['message' => 'Invalid Shiprocket credentials.']);
        }

        // Save new token and time
        $token = $token_data['token'];
        update_option('edpc_auth_token', $token);
        update_option('edpc_token_time', time());
    } else {
        $token = $cached_token;
    }

    // Check mode from settings
    $api_mode = get_option('edpc_api_mode', 'pincode'); // 'pincode' or 'estimate'
    $pickup_pincode = get_option('edpc_pickup_pincode', '');

    if ($api_mode === 'estimate' && !empty($pickup_pincode)) {
        // Build query parameters
        $query_args = [
            'pickup_postcode'   => $pickup_pincode,
            'delivery_postcode' => $pincode,
            'cod'               => 0,
            'weight'            => 0.5,
        ];

        $estimate_url = add_query_arg($query_args, 'https://apiv2.shiprocket.in/v1/external/courier/serviceability');

        $estimate_response = wp_remote_get($estimate_url, [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);

        if (is_wp_error($estimate_response)) {
            wp_send_json_error(['message' => 'Failed to check estimated delivery.']);
        }

        $estimate_result = json_decode(wp_remote_retrieve_body($estimate_response), true);

        if (
            isset($estimate_result['data']['available_courier_companies']) && 
            !empty($estimate_result['data']['available_courier_companies'])
        ) {
            $firstCourier = $estimate_result['data']['available_courier_companies'][0];
            $estimatedDeliveryDays = $firstCourier['estimated_delivery_days'] ?? 'N/A';
            $estimatedDeliveryDate = $firstCourier['etd'] ?? 'N/A';

            wp_send_json_success([
                'estimated_delivery_days' => $estimatedDeliveryDays,
                'estimated_delivery_date' => $estimatedDeliveryDate,
            ]);
        } else {
            wp_send_json_error(['message' => $estimate_result['message'] ?? 'No courier available for this route.']);
        }

    } else {
        // Use the token to call pincode API
        $pin_response = wp_remote_get("https://apiv2.shiprocket.in/v1/external/open/postcode/details?postcode=" . $pincode, [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ]
        ]);

        if (is_wp_error($pin_response)) {
            wp_send_json_error(['message' => 'Failed to check pincode.']);
        }

        $result = json_decode(wp_remote_retrieve_body($pin_response), true);

        if (!empty($result['postcode_details'])) {
            wp_send_json_success([
                'postcode' => $result['postcode_details']['postcode'],
                'city'     => $result['postcode_details']['locality'][0] ?? 'N/A',
            ]);
        } else {
            wp_send_json_error(['message' => $result['message'] ?? 'Unknown error']);
        }
    }
}
