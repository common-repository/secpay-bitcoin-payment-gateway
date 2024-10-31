<?php

// CSS
add_action('wp_enqueue_scripts', 'secpay_payment_design', 100);
function secpay_payment_design() {
    wp_enqueue_style('secpay-css', SECPAY_GATEWAY_FOR_WOOCOMMERCE_MAIN_URL_PATH . 'css/secpay.css');
}
