<?php
/**
 * Plugin Name: CryptoWoo Bitcoin Fee
 * Plugin URI: https://github.com/Olsm/cryptowoo-bitcoin-fee
 * GitHub Plugin URI: Olsm/cryptowoo-bitcoin-fee
 * Description: Add Bitcoin transaction fee to order total. Requires CryptoWoo main plugin.
 * Version: 1.0
 * Author: Olav Småriset
 * Author URI: https://github.com/Olsm
 * License: GPLv2
 * Text Domain: cryptowoo-bch-addon
 * Domain Path: /lang
 * WC tested up to: 3.2.5
 *
 */

add_action( 'woocommerce_cart_calculate_fees', 'wc_add_surcharge' );
add_action( 'plugins_loaded', 'cwbf_add_fields', 10 );
add_action( 'wp_enqueue_scripts', 'enqueue_scripts' );

function wc_add_surcharge() {

	// Make sure we are on the update_order_review or checkout calls
	if ( !isset($_REQUEST["wc-ajax"]) ) {
		return;
	}

	// Make sure the chosen payment method is cryptowoo
	if (WC()->session->chosen_payment_method != "cryptowoo") {
		return;
	}

	// Return if payment currency is not btc to not add fee
	$payment_currency = get_payment_currency_from_request();
	if ($payment_currency != "BTC") return;


	// Option for tx fees (speed of forwarding tx)
	$fee_option = "hourFee";
	$option     = get_option( "cryptowoo_payments" );
	if ( isset( $option["fee_option"] ) ) {
		$fee_option = $option["fee_option"];
	}

	try {
		$fee_satoshi  = get_recommended_bitcoin_fee( $fee_option );
		$fee_per_byte = satoshi_to_usd( $fee_satoshi );
		$fee          = $fee_per_byte * 226;
		WC()->cart->add_fee( 'Surcharge (Bitcoin transaction fee)', $fee, true, 'standard' );
	} catch ( Exception $e ) {
		// ToDo: log error message
		// ToDo: Show error to customer and disable checkout button
	}
}

function get_recommended_bitcoin_fee( $fee_option ) {
	$request = wp_remote_get( "https://bitcoinfees.earn.com/api/v1/fees/recommended" );

	if ( is_wp_error( $request ) ) {
		$message = $request->get_error_message();
		throw new Exception( $message );
	} else if ( isset( $request['body'] ) ) {
		$recommended_fees = json_decode( $request['body'] );
		if ( isset( $recommended_fees->$fee_option ) ) {
			return $recommended_fees->$fee_option;
		}
	}

	throw new Exception( "Could not get recommended fee for fee option {$fee_option}" );
}

function satoshi_to_usd( $satoshi ) {
	$btc     = $satoshi / 100000000;
	$btc_usd = CW_ExchangeRates::get_exchange_rate( "BTC" );

	if ( empty( $btc_usd ) ) {
		throw new Exception( "Could not get usd amount from satoshi amount" );
	}

	return $btc * $btc_usd;
}

function get_payment_currency_from_request() {
	// Get the post data (all data in checkout form)
	$post_data = [];
	if ($_REQUEST["wc-ajax"] == "update_order_review") {
		if (!isset($_POST["post_data"])) {
			// ToDo: Log error
			return false;
		}
		parse_str($_POST["post_data"], $post_data);

	} else if ($_REQUEST["wc-ajax"] == "checkout") {
		$post_data = $_POST;
	} else {
		// ToDo: Log error
		return false;
	}

	// Make sure payment currency exist in post data
	if (!isset($post_data["payment_currency"])) {
		// ToDo: Log error
		return false;
	}

	return $post_data["payment_currency"];;
}

/**
 * Register and enqueues public-facing JavaScript files.
 */
function enqueue_scripts() {
	if ( is_checkout() ) {
		wp_enqueue_script( 'cryptowoo-bitcoin-fee',
			plugins_url( 'js/update-checkout.js', __FILE__ ),
			[ 'wc-checkout' ],
			1
		);
	}
}

/**
 * Add Redux options
 */
function cwbf_add_fields() {
	Redux::setField( 'cryptowoo_payments', array(
		'section_id' => 'rates',
		'id'         => 'fee_option',
		'type'       => 'select',
		'title'      => sprintf( __( 'Add %s Fee', 'cryptowoo-bitcoin-fee' ), 'Bitcoin' ),
		'subtitle'   => sprintf( __( 'Calculate %s transaction fee by confirmation time.', 'cryptowoo' ), 'Bitcoin' ),
		'desc'       => '',
		'options'    => array(
			'hourFee'     => __( 'one hour', 'cryptowoo-bitcoin-fee' ),
			'halfHourFee' => __( 'half hour', 'cryptowoo-bitcoin-fee' ),
			'fastestFee'  => __( '10 minutes', 'cryptowoo-bitcoin-fee' )
		),
		'default'    => 'hourFee',
		'select2'    => array( 'allowClear' => false )
	) );
}