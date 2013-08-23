jQuery(document).ready(function($) {
	$( '.it-exchange-recurring-payment-time-options' ).live('change', function() {
		var value = $( 'option:selected', this ).val();
		console.log( value );
		if ( 'forever' === value ) {
			$( '.it-exchange-recurring-payment-auto-renew' ).addClass( 'hidden' );
		} else {
			$( '.it-exchange-recurring-payment-auto-renew' ).removeClass( 'hidden' );
		}
	});
	
	$( '.it-exchange-recurring-payment-auto-renew' ).live('click', function() {
		var value = $( 'input[name=it_exchange_recurring_payments_auto_renew]', this ).val();
		console.log( value );
		if ( 'off' === value ) {
			$( 'input[name=it_exchange_recurring_payments_auto_renew]', this ).val( 'on' );
			$( this ).css( 'color', 'green' );
			$( this ).attr( 'title', 'Auto-Renew: ON' );
		} else {
			$( 'input[name=it_exchange_recurring_payments_auto_renew]', this ).val( 'off' );
			$( this ).css( 'color', 'black' );
			$( this ).attr( 'title', 'Auto-Renew: OFF' );
		}
	});
});