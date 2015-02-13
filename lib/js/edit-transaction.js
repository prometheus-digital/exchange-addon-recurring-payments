jQuery(document).ready(function($) {
	$( '#recurring-payment-subscriber-expires' ).datepicker({
        dateFormat: $( 'input[name=it_exchange_recurring-payment_date_picker_format]' ).val(),
	});
});