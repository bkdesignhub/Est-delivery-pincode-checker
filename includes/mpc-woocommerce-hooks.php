<?php
// Auto display Pincode Checker on Single Product Page
add_action( 'woocommerce_single_product_summary', function() {

    echo '<div class="product-pincode-checker mpc-pincode-box">
        <div style="display: flex; align-items: center; margin-bottom: 10px;">
            <h4 style="margin: 0; margin-left: 8px; font-size: 16px; font-weight: bold;">Check Delivery Availability</h4>
        </div>';

    // Output your existing shortcode → full form UI
    echo do_shortcode('[delivery_pincode_form]');

    echo '</div>';

}, 30 );