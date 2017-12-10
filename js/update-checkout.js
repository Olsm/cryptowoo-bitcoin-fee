/**
 * Updated the checkout when change the payment method.
 */
(function ( $ ) {
	'use strict';

	$(function () {
		$( document.body ).on( 'change', 'select[name="payment_currency"]', function () {
			$( 'body' ).trigger( 'update_checkout' );
		});
	});
}( jQuery ));
