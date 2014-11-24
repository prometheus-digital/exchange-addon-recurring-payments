jQuery(document).ready(function($) {
	$( '#it-exchange-recurring-payment-settings' ).on( 'change', '.it-exchange-recurring-payment-time-options', function() {
		var value = $( 'option:selected', this ).val();
		if ( 'forever' === value ) {
			$( '.it-exchange-recurring-payment-auto-renew' ).addClass( 'hidden' );
		} else {
			$( '.it-exchange-recurring-payment-auto-renew' ).removeClass( 'hidden' );
		}
	});
	
	$( '#it-exchange-recurring-payment-settings' ).on( 'click', '.it-exchange-recurring-payment-auto-renew', function() {
		// var value = $( 'input[name=it_exchange_recurring_payments_auto_renew]', this ).val();
		// console.log( value );
		if ( $( this ).hasClass( 'auto-renew-on' ) ) {
			$( 'input[name=it_exchange_recurring_payments_auto_renew]', this ).val( 'off' );
			$( this ).toggleClass( 'auto-renew-off' ).toggleClass( 'auto-renew-on' );
			$( this ).tooltip({
				content: 'Auto-Renew: OFF'
			});
		} else {
			$( 'input[name=it_exchange_recurring_payments_auto_renew]', this ).val( 'on' );
			$( this ).toggleClass( 'auto-renew-off' ).toggleClass( 'auto-renew-on' );
			$( this ).tooltip({
				content: 'Auto-Renew: ON'
			});
		}
	}).tooltip({
      position: {
        my: "center bottom",
        at: "center top",
      }
    });;
});