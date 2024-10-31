<?php
/**
 * Update order status Ajax function
 */
add_action( 'wp_ajax_update_order_status', 'secpay_ajax_check_order_status_callback' );
add_action( 'wp_ajax_nopriv_update_order_status', 'secpay_ajax_check_order_status_callback' );

/**
 * Function to update order status
 *
 * @param  int $order_id The order ID
 * @return void
 */
function secpay_ajax_check_order_status_callback( $order_id ) {

    // Check for errors
    if ( ! isset( $_POST['order_id'] ) ) {
        wp_send_json_error( 'Order ID not specified' );
        wp_die();
    }

    $order_id = $_POST['order_id'];
    $st = status_chack( $order_id );
    wp_send_json_success( $st );
    wp_die();
}


// Ajax Update order status after 15 min
add_action( 'wp_ajax_update_order_status_interval', 'update_order_status_interval_callback' );
add_action( 'wp_ajax_nopriv_update_order_status_interval', 'update_order_status_interval_callback' );
function update_order_status_interval_callback() {
    global $woocommerce, $wpdb;
    $order_id = $_POST['order_id'];

    $instance = WC_Secpay_Gateway::getInstance();
    $checkout_flow = $instance->getCheckoutFlow();
    $order = wc_get_order( $order_id );
    $st = status_chack( $order_id );
    $secpaycheckoutflow = get_post_meta( $order_id, 'secpaycheckoutflow', true );
    
    if( !empty( $order )) {
        if( $st == 'Pending' ) {
            if( $secpaycheckoutflow == 'asynchronous' ) {
                $st = 'asynchronous';
                $order->update_status( 'pending' );
            }else{
                $order->update_status( 'cancelled' );
            }
        }
        if( $st == 'Review' ) {
            $order->update_status( 'completed' );
        }
        if( $st == 'Complete' ) {
            $order->update_status( 'completed' );
        }
    }
    wp_send_json_success( $st );
    wp_die();
}


// Ajax checkflow change on PAY LATER
add_action( 'wp_ajax_paylater_checkout_flow', 'paylater_checkout_flow_callback' );
add_action( 'wp_ajax_nopriv_paylater_checkout_flow', 'paylater_checkout_flow_callback' );
function paylater_checkout_flow_callback() {
    $order_id = $_POST['order_id'];
    update_post_meta( $order_id, 'secpaycheckoutflow', 'asynchronous' );
    wp_die();
}

// Ajax cancel order on CANCEL PAYMENT
add_action( 'wp_ajax_secpay_cancel_payment_order', 'secpay_cancel_payment_order_callback' );
add_action( 'wp_ajax_nopriv_secpay_cancel_payment_order', 'secpay_cancel_payment_order_callback' );
function secpay_cancel_payment_order_callback() {
    $order_id = $_POST['order_id'];
    $order = wc_get_order( $order_id );
    if( !empty( $order )) {
        $order->update_status( 'cancelled' );  
    }
    wp_die();
}
