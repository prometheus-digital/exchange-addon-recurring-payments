jQuery(document).ready(function($) {
	$( '#it-exchange-recurring-payment-settings' ).on( 'change', '#it-exchange-recurring-payments-enabled', function() {
		$( '#recurring-payment-options' ).toggleClass( 'hidden' );
	});
	$( '#it-exchange-recurring-payment-settings' ).on( 'change', '#it-exchange-recurring-payments-auto-renew', function() {
		$( '#trial-period-settings' ).toggleClass( 'hidden' );
	});
	$( '#it-exchange-recurring-payment-settings' ).on( 'change', '#it-exchange-recurring-payments-trial-enabled', function() {
		$( '#trial-period-options' ).toggleClass( 'hidden' );
	});
});