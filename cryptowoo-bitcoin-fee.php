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

add_action( 'woocommerce_cart_calculate_fees', 'wc_add_surcharge' );

function wc_add_surcharge() {
	global $woocommerce;

	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return;
	}

	// ToDo: add CryptoWoo setting for fee option
	/** Options for how fast tx can be forwarded
	 * hourFee = one hour
	 * halfHourFee = half hour
	 * fastestFee = 10 minutes */
	$fee_option = "hourFee";

	try {
		$fee_satoshi = get_recommended_bitcoin_fee($fee_option);
		$fee_per_byte = satoshi_to_usd($fee_satoshi);
		$fee = $fee_per_byte * 226;
		$woocommerce->cart->add_fee( 'Surcharge (Bitcoin transaction fee)', $fee, true, 'standard' );
	} catch (Exception $e) {
		// ToDo: log error message
		// ToDo: Show error to customer and disable checkout button
	}
}

function get_recommended_bitcoin_fee($fee_option) {
	$request = wp_remote_get( "https://bitcoinfees.earn.com/api/v1/fees/recommended" );

	if ( is_wp_error( $request ) ) {
		$message = $request->get_error_message();
		throw new Exception($message);
	} else if (isset($request['body'])) {
		$recommended_fees = json_decode( $request['body'] );
		if ( isset( $recommended_fees->$fee_option ) ) {
			return $recommended_fees->$fee_option;
		}
	}

	throw new Exception("Could not get recommended fee for fee option {$fee_option}");
}

function satoshi_to_usd($satoshi) {
	$btc = $satoshi / 100000000;
	$btc_usd = CW_ExchangeRates::get_exchange_rate("BTC");

	if ( empty($btc_usd) ) {
		throw new Exception("Could not get usd amount from satoshi amount");
	}

	return $btc * $btc_usd;
}