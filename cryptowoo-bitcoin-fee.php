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

add_action( 'woocommerce_cart_calculate_fees', 'wc_add_surcharge' );// Options page
add_action( 'plugins_loaded', 'cwbf_add_fields', 10 );

function wc_add_surcharge() {
	global $woocommerce;

	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return;
	}

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
		$woocommerce->cart->add_fee( 'Surcharge (Bitcoin transaction fee)', $fee, true, 'standard' );
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
