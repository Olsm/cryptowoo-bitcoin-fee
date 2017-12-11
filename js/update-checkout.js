/**
 * Update the checkout when changing the payment currency.
 */
(function ( $ ) {

    'use strict';

    $(function () {
        var previous;
        var element;

        $( document.body ).on( 'change', 'select[name="payment_currency"]', function () {
            if (element.value === "BTC" || previous === "BTC") {
                $( 'body' ).trigger( 'update_checkout' );
            }
            previous = element.value;
        });

        $ ( document.body ).on( 'focus click', 'select[name="payment_currency"]', function () {
            if (!previous) {
                previous = element.value;
            }
        } );

        $ ( document.body ).on( 'updated_checkout', function () {
            element = document.getElementById("payment_currency");
            if (previous) {
                element.value = previous;
            }
        } );

    });
}( jQuery ));