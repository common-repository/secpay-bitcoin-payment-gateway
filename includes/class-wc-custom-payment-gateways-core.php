<?php 
class WC_Secpay_Gateway extends WC_Payment_Gateway {
    public $api_url;
    /**
     * Class constructor
    */
    private static $instance = null;
        
    public function __construct() {
        $this->id = 'secpay';
        $this->icon = SECPAY_GATEWAY_FOR_WOOCOMMERCE_MAIN_URL_PATH.'images/bitcoin.png';
        $this->has_fields = true;
        $this->method_title = 'SecPay';
        $this->method_description = 'Accept Bitcoin, receive Euro or CHF';
        $this->supports = array(
            'products'
        );
        $this->init_form_fields();
        $this->init_settings();
        $this->title = $this->get_option( 'title' );
        $this->description = $this->get_option( 'description' );
        $this->enabled = $this->get_option( 'enabled' );
        $this->x_api_key = $this->get_option ('x_api_key');
        $this->mode = $this->get_option ('mode');
        $this->checkout_flow = $this->get_option ('checkout_flow');
        
        if($this->mode == 'sandbox') {
            $this->api_url = "https://api.sandbox.secpay.io";
        }else{
            $this->api_url = "https://api.secpay.io";
        }
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );    
    }

    public static function getInstance() {
        if( !self::$instance ) {
            self::$instance = new WC_Secpay_Gateway();
        }
        return self::$instance;
    }

    /**
     * Plugin options
    */
    public function init_form_fields(){
        $this->form_fields = array(
            'enabled' => array(
                'title'       => 'Enable/Disable',
                'label'       => 'Enable SecPay Payment Gateway',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no'
            ),
            'title' => array(
                'title'       => 'Title',
                'type'        => 'text',
                'description' => 'This controls the title which the user sees during checkout.',
                'default'     => 'Bitcoin',
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => 'Description',
                'type'        => 'textarea',
                'description' => 'This controls the description which the user sees during checkout.',
                'default'     => 'Pay with Bitcoin using SecPay.io',
            ),
            'mode' => array(
                'title'       => 'Mode',
                'type'        => 'select',
                'description' => 'Place the payment gateway in Sandbox mode using Sandbox API.',
                'desc_tip'    => true,
                'default'     => 'sandbox',
                'options'     => array(
                    'sandbox' => 'Sandbox (Blockcypher Testnet)',
                    'production'  => 'Production (Mainnet)'
                ),
            ),
            'checkout_flow' => array(
                'title'       => 'Checkout Flow',
                'type'        => 'select',
                'description' => '',
                'desc_tip'    => false,
                'default'     => 'force',
                'options'     => array(
//                    'asynchronous' => 'Asynchronous',
                    'force'  => 'Show wallet and BTC amount on order confirmation page'
                ),
            ),
            'x_api_key' => array(
                'title'       => 'API key',
                'type'        => 'text',
                'description' => 'Contact info@secpay.io to request your personal API key',
                'desc_tip'    => true,
                'default'     => '',
            ),
        );
    }

    /**
     * Checkout page payment view
    */
    public function payment_fields() {

        global $wpdb, $wp;
        $current_action = current_action();

        $three_digit_code = get_woocommerce_currency();
        $current_exchnage_rate = secpayUpdateExchangeRatesEverytime();

        if( !empty( $current_exchnage_rate ) ) {
            $btc_rate = $current_exchnage_rate[$three_digit_code];
            $single_exchange_timestamp = $current_exchnage_rate['timestamp'];
        }else{
            $single_exchange_timestamp = get_option( 'single_exchange_timestamp' );
            $btc_rate_array = get_option( 'single_euro_btc' );
            if( $three_digit_code == 'EUR' || $three_digit_code == 'CHF'){
                $btc_rate = $btc_rate_array[$three_digit_code];
            }else{
                $btc_rate = $btc_rate_array['EUR'];
            }
        }
        $amountInBtc = WC()->cart->total * $btc_rate;

        if ( $this->get_description() ) {
            $description = $this->get_description();
        }else{
            $description = 'Pay fast and safe with Bitcoin';
        }

        //<p>1 '. $three_digit_code .' = ' . $btc_rate . ' BTC </p>
        if ( $current_action == "wc_ajax_update_order_review" ) {
            echo '<fieldset><div class="secpay-checkout-paymentinfo">
                    <p>'.$description.'</p>
                    <div class="total-amount">Total amount: ' . number_format_i18n($amountInBtc, 8) . ' BTC </div>
                    <div class="secpay-amount-exrate">
                        <p>Exchange rate: ' .number_format_i18n(1/$btc_rate, 2 ).' '.$three_digit_code.'</p>
                        <p>Exchange rate timestamp: '.date("d-m-Y H:i:s", $single_exchange_timestamp).'</p>
                    </div>
                    <p>Comparable market prices at <a target="_blank" href="https://coinmarketcap.com/">https://coinmarketcap.com/</a></p>
                </div>
                <div class="clear"></div></fieldset>';
        } 
        
        if( $current_action == "the_content" ) {
            $order_id = $wp->query_vars['order-pay'];
            $order = new WC_Order( $order_id );
            //$query = $wpdb->get_results ("SELECT * FROM  " . $wpdb->prefix . "secpay_order_amount WHERE order_id = $order_id ");
            $query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}secpay_order_amount WHERE order_id = %d",$order_id);
            $query = $wpdb->get_results($query);
            $btc_address_rate_update = secpayUpdateExchangeRatesEditpay($order_id, $order->get_total(), $three_digit_code);

            //<p> 1 '. $three_digit_code .' = ' . $btc_rate . ' BTC</p>
            echo '<div class="secpay-order-pay-wrapper">
                <p><b>Send: '.$btc_address_rate_update["amount"].' '.$three_digit_code.'</b></p>
                <div class="secpay-order-pay-innerwrapper">
                    <img src = "https://chart.googleapis.com/chart?chs=225x225&cht=qr&chl=bitcoin:'.$btc_address_rate_update["btcAddress"].'?amount='.$btc_address_rate_update["amount"] . '" />
                    <div>
                        <p> Total amount: ' .number_format_i18n($btc_address_rate_update["amount"], 8).  ' ' .$three_digit_code .' </p>
                        <p>Exchange rate: ' .number_format_i18n(1/$btc_address_rate_update["btcRate"], 2) .' '.$three_digit_code.'</p>
                        <p>Exchange rate timestamp: '.date("d-m-Y H:i:s", $btc_address_rate_update["timestamp"]).'</p>
                    </div>
                </div>
                <div class="secpay-btc-address">Bitcoin address: <b id ="btcAddress">'  .$btc_address_rate_update["btcAddress"]. '</b></div>
            </div>';
        }
    }

    /*
    * processing the payments
    */
    public function process_payment( $order_id ) { 
        global $woocommerce, $wpdb;
        $instance = WC_Secpay_Gateway::getInstance();
        $url = $instance->getApiUrl().'/';
        $order = wc_get_order( $order_id );
        $order->update_status( 'pending' );
        $note = '<b>Waiting for payment<b>';
        $order->add_order_note($note);
        WC()->cart->empty_cart();
        $cart_total_euro = $order->get_total();
        
        $body = \json_encode([
            'transactionId' => $order->get_order_number(),
            'amount' => $cart_total_euro,
            'currency' => get_woocommerce_currency()
        ]);
        error_log("SIMBOL" . get_woocommerce_currency());
        $response = wp_remote_post( $url, array(
                'method' => 'POST',
                'headers' => array('Content-Type' => 'application/json; charset=utf-8', 
                        'x-api-key' => $this->x_api_key, 
                        'fiat' => get_woocommerce_currency()
                    ),
                'body' => $body
            )
        );
        update_post_meta( $order->get_id(), 'secpaycheckoutflow', 'force' );
        $body = json_decode( $response['body'], true );
        $date_of_order = date("d-m-Y H:i:s", $body["timestamp"]);
        $table_name = $wpdb->prefix . 'secpay_order_amount';
        $wpdb->insert( 
            $table_name, 
            array( 
                'order_id' => $order->get_id(),
                'currency' => $body['currency'],
                'order_amount_in_btc' => $body['amount'],
                'order_amount_in_eur' => (float) $cart_total_euro,
                'address' => $body['btcAddress'],
                'time_stamp' => $date_of_order, 
            ) 
        );
        
        return array(
            'result'    => 'success',
            'redirect'  => $this->get_return_url( $order )
        );
    }

    public function getApiUrl(){
        return $this->api_url;
    }

    public function getCheckoutFlow(){
        return $this->checkout_flow;
    }

    public function getApiKey(){
        return $this->x_api_key;
    }
    
}

require_once 'class-available-payment-gateways.php';

require_once 'class-thankyou-page.php';

require_once 'class-update-exchange-rate.php';

require_once 'class-update-order-status.php';

require_once 'class-status-checker.php';

require_once 'class-include-css.php';

require_once 'class-ajax-status-handler.php';
