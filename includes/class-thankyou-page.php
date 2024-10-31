<?php

// On thank you page show QR
add_action( 'woocommerce_thankyou', 'secpay_thank_you' );
function secpay_thank_you($order_id) {

    global $woocommerce, $wpdb;
    $order = wc_get_order( $order_id );
    $payment_method = $order->get_payment_method();
    $order_status  = $order->get_status();
    //var_dump( $order_status );
    if( $payment_method == 'secpay' && $order_status == "pending" ) {

        $instance = WC_Secpay_Gateway::getInstance();
        $url = $instance->getApiUrl();
        $checkout_flow = $instance->getCheckoutFlow();
        if( $checkout_flow == "force" ){
            $flow_html = '';
        }else{
            $flow_html = '<p><a id="secpay_pay_later" href="JavaScript:void(0);">Pay later</a></p>';
        }
        //$query = $wpdb->get_results ("SELECT * FROM  " . $wpdb->prefix . "secpay_order_amount WHERE order_id = $order_id ");
        $query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}secpay_order_amount WHERE order_id = %d",$order_id);
        $query = $wpdb->get_results($query);
        $btc_rate = $query[0]->order_amount_in_btc;
        $order_date = (array) $order->get_date_created();
        $add_min = date("Y-m-d H:i:s", strtotime("+15 minutes", strtotime( $order_date["date"] )) );
        ?>

        <script>
            var countinner;
            setTimeout(function() {
                jQuery('#secpay-payment-succ-qr').show();
            }, 1000);
            jQuery(document).on("click", "#check_status_btn", function(e) {
                status_ajax_intreval();
            });
            jQuery(document).on("click", "#secpay_pay_later", function(e) {
                change_checkout_flow_method();
            });
            jQuery(document).on("click", "#secpay_pay_cancel_btn", function(e) {
                secpay_cancel_payment_order();
            });

            function secpay_cancel_payment_order() {
                jQuery.ajax({
                    url: "<?php echo admin_url('admin-ajax.php'); ?>",
                    type: 'POST',
                    data: {
                        'action': 'secpay_cancel_payment_order',
                        'order_id': <?php echo $order_id; ?>
                    },
                    success: function(response) {
                        jQuery('.secpay-bitcoin-qrcode, .second-time, .manully-status-check, .secpay-countdown-order, .secpay-bottom-btn, .secpay-order-status').remove();
                        jQuery("#secpay-payment-succ-qr").append("<p><b>Your order is canceled.</b></p>");
                    },
                });
            }

            function change_checkout_flow_method() {
                jQuery.ajax({
                    url: "<?php echo admin_url('admin-ajax.php'); ?>",
                    type: 'POST',
                    data: {
                        'action': 'paylater_checkout_flow',
                        'order_id': <?php echo $order_id; ?>
                    },
                    success: function(response) {
                        jQuery('.secpay-bitcoin-qrcode, .second-time, .manully-status-check, .secpay-countdown-order, .secpay-bottom-btn, .secpay-order-status').remove();
                        jQuery("#secpay-payment-succ-qr").append("<p><b>Thank you for your order.</b></p>");
                    },
                });
            }

            function status_ajax_intreval() {
                jQuery("#status_check_time-parent").hide();
                jQuery("#status_updating").show();
                jQuery.ajax({
                    url: "<?php echo admin_url('admin-ajax.php'); ?>",
                    type: 'POST',
                    data: {
                        'action': 'update_order_status',
                        'order_id': <?php echo $order_id; ?>
                    },
                    success: function(response) {
                        jQuery('#order_status').html(response.data);
                        if (response.data == "Complete" || response.data == "Review") {
                            jQuery('.secpay-bitcoin-qrcode, .second-time, .manully-status-check, .secpay-countdown-order, .secpay-bottom-btn, .secpay-order-status').remove();
                            //jQuery("#secpay-payment-succ-qr").append("<p><b>Your payment is successful.</b></p>");
                            update_status_ajax_intreval();
                        }
                        clearInterval(countinner);
                        jQuery("#status_check_time").text("30");
                        jQuery("#status_check_time-parent").show();
                        jQuery("#status_updating").hide();
                        check_inner_time();
                    },
                });
            }

            function update_status_ajax_intreval() {
                jQuery.ajax({
                    url: "<?php echo admin_url('admin-ajax.php'); ?>",
                    type: 'POST',
                    data: {
                        'action': 'update_order_status_interval',
                        'order_id': <?php echo $order_id; ?>
                    },
                    success: function(response) {
                        if (response.data == "Complete" || response.data == "Review") {
                            jQuery("#secpay-payment-succ-qr").append("<p><b>Your payment is successful.</b></p>");
                        }
                        if (response.data == "Pending") {
                            jQuery('.secpay-pay-bitaddress').remove();
                            jQuery("#secpay-payment-succ-qr").append("<p><b>Your order is canceled.</b></p>");
                        }
                    },
                });
            }
            
            function check_inner_time() {
                var status_check_time = jQuery("#status_check_time").text();
                //console.log(status_check_time);
                if (status_check_time !== "") {
                    countinner = setInterval(function() {
                        if (status_check_time < 1) {
                            clearInterval(countinner);
                            status_ajax_intreval();
                        } else {
                            status_check_time--;
                            if (status_check_time < "10") {
                                status_check_time = "0" + status_check_time;
                            }
                            jQuery("#status_check_time").text(status_check_time);
                        }
                    }, 1000);
                }
            }

            setTimeout(function() {
                check_inner_time();
            }, 500);

            var countDownDate = new Date("<?php echo $add_min; ?>");
            countDownDate.toUTCString();
            countDownDate = Math.floor(countDownDate.getTime());
            var x = setInterval(function() {
                var d1 = new Date();
                d1.toUTCString();
                var now = new Date(d1.getUTCFullYear(), d1.getUTCMonth(), d1.getUTCDate(), d1.getUTCHours(), d1
                    .getUTCMinutes(), d1.getUTCSeconds());
                now.toUTCString();
                now = Math.floor(now.getTime());
                var distance = countDownDate - now;
                var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                var seconds = Math.floor((distance % (1000 * 60)) / 1000);
                if (minutes < "10") {
                    if (minutes >= "0") {
                        minutes = "0" + minutes;
                    }
                }
                if (seconds < "10") {
                    if (seconds >= "0") {
                        seconds = "0" + seconds;
                    }
                }
                jQuery("#countdown").html(minutes + ":" + seconds);
                if (minutes <= 0) {
                    if (seconds <= 0) {
                        clearInterval(x);
                        clearInterval(countinner);
                        update_status_ajax_intreval();
                        jQuery('.secpay-bitcoin-qrcode, .second-time, .manully-status-check, .secpay-countdown-order, .secpay-bottom-btn, .secpay-order-status').remove();
                    }
                }
            }, 1000);
        </script>
    <?php
        $get_status = status_chack($order_id);
        $three_digit_code = get_woocommerce_currency();
        $current_exchnage_rate = secpayUpdateExchangeRatesEverytime();
        if( !empty( $current_exchnage_rate ) ) {
            $btc_rate_one = $current_exchnage_rate[$three_digit_code];
            $single_exchange_timestamp = $current_exchnage_rate['timestamp'];
        }else{
            $single_exchange_timestamp = get_option( 'single_exchange_timestamp' );
            $btc_rate_array = get_option( 'single_euro_btc' );
            if( $three_digit_code == 'EUR' || $three_digit_code == 'CHF'){
                $btc_rate_one = $btc_rate_array[$three_digit_code];
            }else{
                $btc_rate_one = $btc_rate_array['EUR'];
            }
        }

        $amountInBtc = $order->get_total() * $btc_rate_one;
        $secpay_image = SECPAY_GATEWAY_FOR_WOOCOMMERCE_MAIN_URL_PATH.'images/secpay.png';
        $secpaycheckoutflow = get_post_meta( $order_id, 'secpaycheckoutflow', true );
        if( $secpaycheckoutflow == 'asynchronous' ) { ?>
            <style>
            .secpay-bitcoin-qrcode,
            .second-time,
            .manully-status-check,
            .secpay-countdown-order,
            .secpay-bottom-btn,
            .secpay-order-status {
                display: none !important;
            }
            </style>
        <?php }
        
        //<p>1 '. $three_digit_code .' = ' . $btc_rate_one . ' BTC </p>
        echo '<div id="secpay-payment-succ-qr">
            <div class="payment-main-heading">
                <h3>Payment with</h3>
                <img src="'.$secpay_image.'">
                <h3 style="color: #3939a7;">SecPay.io</h3>
            </div>
            <div class="secpay-pay-info extra">
                <p>Please send
                '. number_format_i18n( $amountInBtc, 8 ) .' BTC to the Bitcoin address</p>
            </div>
            <div class="secpay-pay-bitaddress">
                    <p><b>'. $query[0]->address .'</b></p>
                </div>
            <div class="secpay-pay-info">
                <p>Exchange rate: ' . number_format_i18n( 1/$btc_rate_one, 2 ) .' '.$three_digit_code.'</p>
                <p>Exchange rate timestamp: '.date("d-m-Y H:i:s", $single_exchange_timestamp).'</p>
                <p>Comparable market prices at <a target="_blank" href="https://coinmarketcap.com/">https://coinmarketcap.com/</a></p>
            </div>
            <div class="secpay-bitcoin-qrcode">
                <img src = "https://chart.googleapis.com/chart?chs=225x225&cht=qr&chl=bitcoin:'.$query[0]->address.'?amount='.$amountInBtc. '" />
            </div>
            <div class="second-time">
                <div id="status_check_time-parent">
                    <p>Payment status will be automatically checked in&nbsp;</p>
                    <div id="status_check_time">30</div>
                    <p>seconds.</p>
                </div>
                <div id="status_updating">Status updating...</div>
            </div>
            <!--div class="manully-status-check"><a id="check_status_btn" href="javascript:void(0);">Click here </a> to check payment status manually.</div-->
            <div class="secpay-order-status">Status: <p id="order_status">'.ucfirst($get_status).'</p></div>
            <div class="secpay-countdown-order">
                <p>Price warranty: </p>
                <div>
                    <p id="countdown"></p>
                    <p>Minutes</p>
                </div>
            </div>
            <!--div class="secpay-bottom-btn">
                <div class="cancel-btn">
                    <a id="secpay_pay_cancel_btn" href="JavaScript:void(0);">Cancel Payment</a>
                </div>
                <div id="secpay_pay_later_wrraper">'.$flow_html.'</div>
            </div-->
        </div>';
    }
}