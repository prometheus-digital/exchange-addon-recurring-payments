<?php
/**
 * iThemes Exchange Recurring Payments Add-on
 * Generic functions
 *
 * @package exchange-addon-recurring-payments
 * @since   1.0.0
 */

/**
 * Sends notification to customer upon specific status changes
 *
 * @since 1.0.0
 *
 * @param IT_Exchange_Customer|int     $customer    iThemes Exchange Customer Object
 * @param string                       $status      Subscription Status
 * @param IT_Exchange_Transaction|bool $transaction Transaction
 */
function it_exchange_recurring_payments_customer_notification( $customer, $status, $transaction = false ) {

	$customer = it_exchange_get_customer( $customer );

	if ( ! $customer instanceof IT_Exchange_Customer ) {
		return;
	}

	switch ( $status ) {

		case 'deactivate':

			$notification = it_exchange_email_notifications()->get_notification( 'recurring-payment-deactivated' );

			it_exchange_send_email( new IT_Exchange_Email( new IT_Exchange_Email_Recipient_Customer( $customer ), $notification, array(
				'customer' => $customer
			) ) );
			break;

		case 'cancel':

			$notification = it_exchange_email_notifications()->get_notification( 'recurring-payment-cancelled' );

			it_exchange_send_email( new IT_Exchange_Email( new IT_Exchange_Email_Recipient_Customer( $customer ), $notification, array(
				'customer' => $customer
			) ) );
			break;

	}

	do_action( 'it_exchange_recurring_payments_customer_notification', $customer, $status, $transaction );

}

/**
 * Updates Expirations dates upon successful payments of recurring products
 *
 * @since 1.0.0
 *
 * @param IT_Exchange_Transaction $transaction iThemes Exchange Transaction Object
 * @param DateTime                $from        From date to base the new expiration date off of.
 *
 * @return void
 */
function it_exchange_recurring_payments_addon_update_expirations( $transaction, DateTime $from = null ) {

	if ( ! empty( $transaction->post_parent ) ) {
		$transaction = it_exchange_get_transaction( $transaction->post_parent );
	}

	foreach ( it_exchange_get_transaction_subscriptions( $transaction ) as $subscription ) {
		$subscription->bump_expiration_date( $from );
	}
}

/**
 * Generates a recurring label
 *
 * @since CHANGEME
 *
 * @param int  $product_id iThemes Exchange Product ID
 * @param bool $show_trial
 *
 * @return string iThemes Exchange recurring label
 */
function it_exchange_recurring_payments_addon_recurring_label( $product_id, $show_trial = true ) {

	if ( ! it_exchange_product_has_feature( $product_id, 'recurring-payments', array( 'setting' => 'recurring-enabled' ) ) ) {
		return '';
	}

	if ( ! it_exchange_get_product_feature( $product_id, 'recurring-payments', array( 'setting' => 'recurring-enabled' ) ) ) {
		return '';
	}

	$trial_enabled        = it_exchange_get_product_feature( $product_id, 'recurring-payments', array( 'setting' => 'trial-enabled' ) );
	$trial_interval       = it_exchange_get_product_feature( $product_id, 'recurring-payments', array( 'setting' => 'trial-interval' ) );
	$trial_interval_count = it_exchange_get_product_feature( $product_id, 'recurring-payments', array( 'setting' => 'trial-interval-count' ) );
	$interval             = it_exchange_get_product_feature( $product_id, 'recurring-payments', array( 'setting' => 'interval' ) );
	$interval_count       = it_exchange_get_product_feature( $product_id, 'recurring-payments', array( 'setting' => 'interval-count' ) );

	if ( ! $interval_count ) {
		return '';
	}

	$rp = new IT_Exchange_Recurring_Profile( $interval, $interval_count );

	$label = ' ' . (string) $rp;

	if ( $trial_enabled && $show_trial ) {
		if ( 'membership-product-type' === it_exchange_get_product_type( $product_id ) && function_exists( 'it_exchange_is_customer_eligible_for_trial' ) ) {
			if ( is_user_logged_in() ) {
				$show_trial = it_exchange_is_customer_eligible_for_trial( it_exchange_get_product( $product_id ) );
			}
		}

		$show_trial = apply_filters( 'it_exchange_recurring_payments_addon_recurring_label_show_trial', $show_trial, $product_id );

		if ( $show_trial && 0 < $trial_interval_count ) {

			$trial = new IT_Exchange_Recurring_Profile( $trial_interval, $trial_interval_count );

			$label .= '&nbsp;' . sprintf( __( '(after %s)', 'LION' ), $trial->get_label( true ) );
		}
	}

	return apply_filters( 'it_exchange_recurring_payments_addon_expires_time_label', $label, $product_id );
}

/**
 * Get the recurring product profile.
 *
 * @since 1.9.0
 *
 * @param int|IT_Exchange_Product $product
 *
 * @return IT_Exchange_Recurring_Profile|null
 */
function it_exchange_get_recurring_product_profile( $product ) {
	$product = it_exchange_get_product( $product );

	$recurring_enabled = $product->get_feature( 'recurring-payments', array( 'setting' => 'recurring-enabled' ) );

	if ( ! $recurring_enabled ) {
		return null;
	}

	$interval       = $product->get_feature( 'recurring-payments', array( 'setting' => 'interval' ) );
	$interval_count = $product->get_feature( 'recurring-payments', array( 'setting' => 'interval-count' ) );

	return new IT_Exchange_Recurring_Profile( $interval, $interval_count );
}

/**
 * Get the recurring product trial profile.
 *
 * @since 1.9.0
 *
 * @param int|IT_Exchange_Product $product
 *
 * @return IT_Exchange_Recurring_Profile|null
 */
function it_exchange_get_recurring_product_trial_profile( $product ) {
	$product = it_exchange_get_product( $product );

	$trial_enabled = $product->get_feature( 'recurring-payments', array( 'setting' => 'trial-enabled' ) );

	if ( ! $trial_enabled ) {
		return null;
	}

	$trial_interval       = $product->get_feature( 'recurring-payments', array( 'setting' => 'trial-interval' ) );
	$trial_interval_count = $product->get_feature( 'recurring-payments', array( 'setting' => 'trial-interval-count' ) );

	return new IT_Exchange_Recurring_Profile( $trial_interval, $trial_interval_count );
}

/**
 * For hierarchical subscriptions.
 * Prints or returns an HTML formatted list of subscriptions and their children
 *
 * @since 1.9.0
 *
 * @param array $product_ids Parent IDs of subscription products
 * @param array $args        array of arguments for the function
 *
 * @return string|null
 */
function it_exchange_recurring_payments_addon_display_subscription_hierarchy( $product_ids, $args = array() ) {
	$args = wp_parse_args( $args, array(
		'echo'         => true,
		'delete'       => true,
		'hidden_input' => true,
	) );

	$echo         = $args['echo'];
	$delete       = $args['delete'];
	$hidden_input = $args['hidden_input'];

	$output = '';
	foreach ( $product_ids as $product_id ) {
		if ( false !== get_post_status( $product_id ) ) {
			$output .= '<ul>';
			$output .= '<li data-child-id="' . $product_id . '"><div class="inner-wrapper">' . get_the_title( $product_id );

			if ( $delete ) {
				$output .= ' <a href data-subscription-id="' . $product_id . '" class="it-exchange-subscription-addon-delete-subscription-child it-exchange-remove-item">&times;</a>';
			}

			if ( $hidden_input ) {
				$output .= ' <input type="hidden" name="it-exchange-subscription-child-ids[]" value="' . $product_id . '" />';
			}

			$output .= '</div>';

			if ( $child_ids = it_exchange_get_product_feature( $product_id, 'subscription-hierarchy', array( 'setting' => 'children' ) ) ) {
				$output .= it_exchange_recurring_payments_addon_display_subscription_hierarchy( $child_ids, array(
					'echo'         => false,
					'delete'       => false,
					'hidden_input' => false
				) );
			}

			$output .= '</li>';
			$output .= '</ul>';
		}
	}

	if ( $echo ) {
		echo $output;
	} else {
		return $output;
	}
}