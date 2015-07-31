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
 * @param object $transaction Transaction
*/
function it_exchange_recurring_payments_customer_notification( $customer, $status, $transaction=false ) {
	
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
		
	do_action( 'it_exchange_recurring_payments_customer_notification', $customer, $status, $transaction );
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
	if ( version_compare( $GLOBALS['it_exchange']['version'], '1.3.0', '<' ) ) {
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

function it_exchange_recurring_payments_addon_interval_string( $interval, $count ) {
	
	if ( 1 < $count ) {
		return $interval . 's';
	} else {
		return $interval;
	}
	
}

/**
 * Generates a recurring label
 *
 * @since CHANGEME
 * @param int $product_id iThemes Exchange Product ID
 * @return string iThemes Exchange recurring label
*/
function it_exchange_recurring_payments_addon_recurring_label( $product_id ) {
	$label = '';
	if ( it_exchange_product_has_feature( $product_id, 'recurring-payments', array( 'setting' => 'recurring-enabled' ) ) ) {
		if ( it_exchange_get_product_feature( $product_id, 'recurring-payments', array( 'setting' => 'recurring-enabled' ) ) ) {
			$trial_enabled        = it_exchange_get_product_feature( $product_id, 'recurring-payments', array( 'setting' => 'trial-enabled' ) );
			$trial_interval       = it_exchange_get_product_feature( $product_id, 'recurring-payments', array( 'setting' => 'trial-interval' ) );
			$trial_interval_count = it_exchange_get_product_feature( $product_id, 'recurring-payments', array( 'setting' => 'trial-interval-count' ) );
			$auto_renew           = it_exchange_get_product_feature( $product_id, 'recurring-payments', array( 'setting' => 'auto-renew' ) );
			$interval             = it_exchange_get_product_feature( $product_id, 'recurring-payments', array( 'setting' => 'interval' ) );
			$interval_count       = it_exchange_get_product_feature( $product_id, 'recurring-payments', array( 'setting' => 'interval-count' ) );
			
		    $interval_types = array(
			    'day'   => __( 'Day', 'LION' ),
			    'week'  => __( 'Week', 'LION' ),
			    'month' => __( 'Month', 'LION' ),
			    'year'  => __( 'Year', 'LION' ),
			);
			$interval_types = apply_filters( 'it_exchange_recurring_payments_interval_types_labels', $interval_types );
		
			if ( 1 == $interval_count ) {
				$label .= '&nbsp;' . sprintf( __( 'every %s', 'LION' ), it_exchange_recurring_payments_addon_interval_string( $interval_types[$interval], $interval_count ) );
			} else if ( 0 < $interval_count ) {
				$label .= '&nbsp;' . sprintf( __( 'every %s %s', 'LION' ), $interval_count, it_exchange_recurring_payments_addon_interval_string( $interval_types[$interval], $interval_count ) );
			}

			if ( $trial_enabled ) {	
				$show_trial = true;
				if ( 'membership-product-type' === it_exchange_get_product_type( $product_id ) ) {
					if ( is_user_logged_in() ) {
						if ( function_exists( 'it_exchange_get_session_data' ) ) {
							$member_access = it_exchange_get_session_data( 'member_access' );
							$children = (array)it_exchange_membership_addon_get_all_the_children( $product_id );
							$parents = (array)it_exchange_membership_addon_get_all_the_parents( $product_id );
							foreach( $member_access as $prod_id => $txn_id ) {
								if ( $prod_id === $product_id || in_array( $prod_id, $children ) || in_array( $prod_id, $parents ) ) {
									$show_trial = false;
									break;
								}								
							}
						}
					}
				}
				
				$show_trial = apply_filters( 'it_exchange_recurring_payments_addon_recurring_label_show_trial', $show_trial, $product_id );
	
				if ( $show_trial && 0 < $trial_interval_count ) {
					$label .= '&nbsp;' . sprintf( __( '(after %s %s free)', 'LION' ), $trial_interval_count, it_exchange_recurring_payments_addon_interval_string( $interval_types[$trial_interval], $trial_interval_count ) );
				}
			}
			
			$label = apply_filters( 'it_exchange_recurring_payments_addon_expires_time_label', $label, $product_id );
		}
	}
	return $label;
}
