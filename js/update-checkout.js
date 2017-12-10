/**
 * Update the checkout when changing the payment currency.
 */
(function ( $ ) {
    var previousCurrency = null;

	'use strict';

	$(function () {
		$( document.body ).on( 'change', 'select[name="payment_currency"]', function () {
            var element = document.getElementById("payment_currency");
            if (element.value === "BTC" || previousCurrency === "BTC") {
                $( 'body' ).trigger( 'update_checkout' );
            }
            previousCurrency = element.value;
		});

        $ ( document.body ).on( 'updated_checkout', function () {
            var element = document.getElementById("payment_currency");
            if (previousCurrency) {
                element.value = previousCurrency;
            }
        } );

	});
}( jQuery ));