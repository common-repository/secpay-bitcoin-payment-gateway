<?php

global $table_prefix, $wpdb;
$secpay_order_amount = $wpdb->prefix . 'secpay_order_amount';
$charset_collate = $wpdb->get_charset_collate();

// Check to see if the table exists already, if not, then create it
if($wpdb->get_var( "show tables like '$secpay_order_amount'" ) != $secpay_order_amount ) {
    $sql = "CREATE TABLE $secpay_order_amount (
        id int(9) NOT NULL AUTO_INCREMENT,
        order_id bigint(20) NOT NULL,
        currency varchar(5) NOT NULL,
        order_amount_in_btc double(16,8) NOT NULL,
        order_amount_in_eur float NOT NULL,
        address varchar(50) NOT NULL,
        time_stamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
