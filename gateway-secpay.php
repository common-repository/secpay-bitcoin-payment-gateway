<?php
/*
 * Plugin Name: SecPay
 * Description: SecPay – accept Bitcoin, receive Euro or CHF
 * Author: savedroid FL GmbH
 * Author URI: https://www.secpay.io/
 * Version: 2.0.0
 * 
 */


require 'vendor/autoload.php';

if (!defined('ABSPATH')) {
  exit;
}

// Check if woocommerce is installed
if ( !defined('SECPAY_GATEWAY_FOR_WOOCOMMERCE_MAIN_URL_PATH') ) {
  define('SECPAY_GATEWAY_FOR_WOOCOMMERCE_MAIN_URL_PATH', plugin_dir_url(__FILE__));
}

// Setup Cron for status update 
add_filter('cron_schedules', 'secpay_cron_add_intervals');
function secpay_cron_add_intervals( $schedules ) {
  $schedules['everyfifteenminutes'] = array(
    'interval' => 1200,
    'display' => __('Every 15 min')
  );
  $schedules['everyfiveminutes'] = array(
    'interval' => 300,
    'display' => __('Every 5 min')
  );
  $schedules['every_two_days'] = array(
    'interval' => 172800,
    'display' => __('Every 48 hours')
  );
  return $schedules;
}

// Enable cron when plugin is active & create cron job when wp is loaded
register_activation_hook( __FILE__, 'secpay_active_schedule_cron' );
add_action('wp', 'secpay_active_schedule_cron');
function secpay_active_schedule_cron(){

  if( ! wp_next_scheduled('secpay_update_order_status') ){
    wp_schedule_event(time(), 'hourly', 'secpay_update_order_status');
  }

  if( ! wp_next_scheduled('secpay_update_exchange_rate') ){
    wp_schedule_event(time(), 'everyfiveminutes', 'secpay_update_exchange_rate');
  }
  
  if( ! wp_next_scheduled('secpay_check_order_status') ){
    wp_schedule_event(time(), 'every_two_days', 'secpay_check_order_status');
  }

  require_once 'includes/class-wc-create-table.php';

}

// Disable cron when plugin is deactivate cron
register_deactivation_hook( __FILE__, 'secpay_deactive_schedule_cron' );
function secpay_deactive_schedule_cron(){
  wp_clear_scheduled_hook( 'secpay_update_order_status' );
  wp_clear_scheduled_hook( 'secpay_update_exchange_rate' );
}
 
add_action( 'plugins_loaded', 'secpay_init_gateway_class' );
function secpay_init_gateway_class() {
  require_once 'includes/class-wc-custom-payment-gateways-core.php';
}