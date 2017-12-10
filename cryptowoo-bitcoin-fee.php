<?php
/**
 * Plugin Name: CryptoWoo Bitcoin Fee
 * Plugin URI:
 * Description: Add Bitcoin transaction fee to order total. Requires CryptoWoo main plugin.
 * Version: 0.1.0
 * Author: Olsm|OlavOlsm|Keys4Coins
 * Author URI: https://www.keys4coins.com
 * License: GPLv2
 * WC tested up to: 3.2.5
 */

add_action( 'woocommerce_cart_calculate_fees','wc_add_surcharge' );

function wc_add_surcharge() {
    global $woocommerce;

    if ( is_admin() && ! defined( 'DOING_AJAX' ) )
        return;

    $fee = 1.00;

    $woocommerce->cart->add_fee( 'Surcharge', $fee, true, 'standard' );
}