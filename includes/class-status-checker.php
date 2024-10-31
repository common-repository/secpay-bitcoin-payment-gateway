<?php

// Status Checker
function status_chack($order_id) {
    $instance = WC_Secpay_Gateway::getInstance();
    $url = $instance->getApiUrl().'/status';
    $transaction_id = $order_id;
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
        $status = 'Pending';
        break;
    case 'review':
        $status = 'Review';
        break;
    case 'unconfirmed':
        $status = 'Pending';
        break;
    case 'cancelled':
        $status = 'Cancel';
        break;
    case 'partially_paid':
        $status = 'Pending';
        break;
    case 'paid':
        $status = 'Complete';
        break;
    }
    return $status;
}