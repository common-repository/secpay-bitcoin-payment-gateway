<?php

// Update order status every 1 hour last 48 hours order
add_action('secpay_update_order_status', 'secpayUpdateOrderStatus');
function secpayUpdateOrderStatus(){
    $instance = WC_Secpay_Gateway::getInstance();
    $url = $instance->getApiUrl().'/status';
    $statuses = ['wc-pending', 'wc-processing', 'wc-on-hold', 'wc-cancelled'];
    $orders = wc_get_orders( ['limit' => -1, 'status' => $statuses, 'payment_method' => 'secpaypay', 'date_created' => '>' . ( time() - 172800 )] );
    if( !empty( $orders )) {
        foreach ($orders as $order) {
            $transaction_id = $order->get_id();
            $secpaycheckoutflow = get_post_meta( $transaction_id , 'secpaycheckoutflow', true );
            $body = \json_encode([
                'transactionId' =>  "$transaction_id"
            ]);
            $response = wp_remote_post( $url, array(
                    'method' => 'POST',
                    'headers' => array('Content-Type' => 'application/json; charset=utf-8', 'x-api-key' => $instance->getApiKey()),
                    'body' => $body
                )
            );
            $body = json_decode( $response['body'], true );
            switch ($body['status']) {
            case 'open':
                if( $secpaycheckoutflow == 'force' ){
                    $order->update_status( 'cancelled' );  
                }else{
                    $order->update_status( 'pending' );
                }
                break;
            case 'review':
                $order->update_status( 'completed' );
                break;
            case 'cancelled':
                $order->update_status( 'cancelled' );
                break;
            case 'partially_paid':
                $order->update_status( 'processing' );
                break;
            case 'paid':
                $order->update_status( 'completed' );
                break;
            }
        }
    }
}

// Every 48 hours. Check before 48 hours order
add_action('secpay_check_order_status', 'secpayCheckOrderStatus');
function secpayCheckOrderStatus() {
    $instance = WC_Secpay_Gateway::getInstance();
    $url = $instance->getApiUrl().'/status';
    $statuses = ['wc-pending', 'wc-processing', 'wc-on-hold'];
    $orders = wc_get_orders( ['limit' => -1, 'status' => $statuses, 'payment_method' => 'secpaypay', 'date_created' => '<' . ( time() - 172800 )] );
    if( !empty( $orders )) {
        foreach ($orders as $order) {
            $transaction_id = $order->get_id();
            $order->update_status( 'cancelled' );
        }
    } 
}