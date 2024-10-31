<?php

// Update exchange rate every five min
add_action('secpay_update_exchange_rate', 'secpayUpdateExchangeRates');
function secpayUpdateExchangeRates(){
    global $wpdb;
    $instance = WC_Secpay_Gateway::getInstance();
    $url = $instance->getApiUrl().'/exchange-rate';
    $all_currency = array("EUR", "CHF");
    $single_eur_chf = array();
    foreach ($all_currency as $val) {
        $headers = [
            'Content-Type' => 'application/json',
            'x-api-key' => $instance->getApiKey(),
            'fiat' => $val
        ];
        $client = new GuzzleHttp\Client([
            'headers' => $headers
        ]);
        $request = $client->Request('GET', $url);
        $body = json_decode($request->getBody()->getContents());
        $single_eur_chf[$val] = number_format((double)$body->$val->BTC->rate, 10, '.', '');
        $single_exchange_time = $body->$val->BTC->timestamp;
    }
    // 1 EURO to BITCOIN
    $single_euro_btc_option = update_option( 'single_euro_btc', $single_eur_chf );
    $single_exchange_timestamp = update_option( 'single_exchange_timestamp', $single_exchange_time );
}

function secpayUpdateExchangeRatesEverytime(){
    global $wpdb;
    $instance = WC_Secpay_Gateway::getInstance();
    $url = $instance->getApiUrl().'/exchange-rate';
    
    $single_eur_chf = array();
    $three_digit_code = get_woocommerce_currency();

    $headers = [
        'Content-Type' => 'application/json',
        'x-api-key' => $instance->getApiKey(),
        'fiat' => $three_digit_code
    ];
    $client = new GuzzleHttp\Client([
        'headers' => $headers
    ]);
    $request = $client->Request('GET', $url);
    $body = json_decode($request->getBody()->getContents());
    $single_eur_chf[$three_digit_code] = number_format((double)$body->$three_digit_code->BTC->rate, 10, '.', '');
    $single_eur_chf['timestamp'] = $body->$three_digit_code->BTC->timestamp;


    return $single_eur_chf;
}


function secpayUpdateExchangeRatesEditpay($orderid, $amount, $currency){

    global $wpdb;
    $instance = WC_Secpay_Gateway::getInstance();
    $url = $instance->getApiUrl().'/';

    $body = \json_encode([
        'transactionId' => $orderid,
        'amount' => $amount,
        'currency' => $currency
    ]);
    error_log("SIMBOL" . $currency);
    $response = wp_remote_post( $url, array(
            'method' => 'PUT',
            'headers' => array('Content-Type' => 'application/json; charset=utf-8', 
                    'x-api-key' => $instance->getApiKey(),
                    'fiat' => $currency
                ),
            'body' => $body
        )
    );

   $body = json_decode( $response['body'], true );

   return $body;

}