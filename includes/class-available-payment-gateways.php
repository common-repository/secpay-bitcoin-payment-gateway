<?php

// Add new payment gateways
add_filter( 'woocommerce_payment_gateways', 'secpay_add_payment_gateway' );
function secpay_add_payment_gateway( $gateways ) {
	$gateways[] = 'WC_Secpay_Gateway'; 
	return $gateways;
}

// Hide Payment gatway when currency unsupport
add_filter('woocommerce_available_payment_gateways','filter_gateways', 1);
function filter_gateways($gateways){
    global $woocommerce;
    $three_digit_code = get_woocommerce_currency();
    $support_currency = array("EUR", "CHF");
    if( !in_array($three_digit_code, $support_currency) ){
        unset($gateways['secpay']);
    }
    return $gateways;
}
