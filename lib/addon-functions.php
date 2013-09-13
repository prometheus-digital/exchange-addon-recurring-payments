<?php
/**
 * iThemes Exchange Recurring Payments Add-on
 * Generic functions
 * @package exchange-addon-recurring-payments
 * @since 1.0.0
*/

/**
 * Sends notification to customer upon specific status changes
 *
 * @since 1.0.0
 * @param object $customer iThemes Exchange Customer Object
 * @param string $status Subscription Status
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

/**
 * Shows the nag when needed.
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_addon_recurring_payments_show_version_nag() {
	if ( $GLOBALS['it_exchange']['version'] < '1.3.0' ) {
		?>
		<div id="it-exchange-add-on-min-version-nag" class="it-exchange-nag">
			<?php printf( __( 'The Recurring Payments add-on requires iThemes Exchange version 1.3.0 or greater. %sPlease upgrade Exchange%s.', 'LION' ), '<a href="' . admin_url( 'update-core.php' ) . '">', '</a>' ); ?>
		</div>
		<script type="text/javascript">
			jQuery( document ).ready( function() {
				if ( jQuery( '.wrap > h2' ).length == '1' ) {
					jQuery("#it-exchange-add-on-min-version-nag").insertAfter('.wrap > h2').addClass( 'after-h2' );
				}
			});
		</script>
		<?php
	}
}
add_action( 'admin_notices', 'it_exchange_addon_recurring_payments_show_version_nag' );
