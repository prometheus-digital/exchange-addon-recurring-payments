<?php

/**
 * Sends notification to customer upon specific status changes
 *
 * @since 1.0.0
*/
function it_exchange_recurring_payments_customer_notification( $customer, $status ) {
	
	$settings = it_exchange_get_option( 'addon_recurring_payments', true );
	
	$subject = '';
	$content = '';
	
	switch ( $status ) {
	
		case 'deactivate':
			$subject = $settings['recurring-payments-deactivate-subject'];
			$content = $settings['recurring-payments-deactivate-body'];
			break;
			
		case 'cancel':
			$subject = $settings['recurring-payments-cancel-subject'];
			$content = $settings['recurring-payments-cancel-body'];
			break;
		
	}
		
	do_action( 'it_exchange_recurring_payments_customer_notification', $customer, $status );
	do_action( 'it_exchange_send_email_notification', $customer->id, $subject, $content );
	
}