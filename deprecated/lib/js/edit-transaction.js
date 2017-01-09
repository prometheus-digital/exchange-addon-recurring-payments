jQuery( document ).ready( function ( $ ) {

	var df = $( 'input[name=it_exchange_recurring-payment_date_picker_format]' ).val();

	$( '.rp-expires' ).datepicker( {
		dateFormat: df,
		prevText  : '',
		nextText  : '',
	} );

} );