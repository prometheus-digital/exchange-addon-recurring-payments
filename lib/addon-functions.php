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

		if ( ! $subscription->is_status( $subscription::STATUS_ACTIVE ) && $subscription->get_transaction()->is_cleared_for_delivery() ) {
			$subscription->set_status( $subscription::STATUS_ACTIVE );
		}

		if ( func_num_args() === 1 && ! $subscription->is_auto_renewing() && $subscription->is_status( $subscription::STATUS_ACTIVE ) ) {
			$from = $subscription->get_expiry_date();
			$now = new DateTime( 'now', new DateTimeZone( 'UTC' ) );

			if ( $from < $now ) {
				$from = $now;
			}
		}

		$subscription->bump_expiration_date( $from );
	}
}

/**
 * Generates a recurring label
 *
 * @since CHANGEME
 *
 * @param int    $product_id iThemes Exchange Product ID
 * @param bool   $show_trial
 * @param string $price
 *
 * @return string iThemes Exchange recurring label
 */
function it_exchange_recurring_payments_addon_recurring_label( $product_id, $show_trial = true, $price = '' ) {

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

	$max = it_exchange_get_product_feature( $product_id, 'recurring-payments', array( 'setting' => 'max-occurrences' ) );

	$label = $price . ' ';

	if ( $max ) {
		$label = sprintf( __( '%d payments of %s', 'LION' ), $max, $price ) . ' ';
	}

	$label .= (string) $rp;

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
 * @since 2.0.0
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
 * @since 2.0.0
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
 * Add fees for free days.
 *
 * @since 2.0.0
 *
 * @param ITE_Cart_Product $item
 * @param ITE_Cart         $cart
 */
function it_exchange_recurring_payments_add_free_days_fees( ITE_Cart_Product $item, ITE_Cart $cart ) {

	$request = ITE_Prorate_Credit_Request::get( $item->get_product(), $cart );

	if ( ! $request ) {
		return;
	}

	if ( $request->get_credit_type() !== 'days' ) {
		return;
	}

	$existing = $item->get_line_items()->with_only( 'fee' )->filter( function( ITE_Fee_Line_Item $fee ) {
		return $fee->has_param( 'is_prorate_days' );
	} );

	if ( $existing->count() > 0 ) {
		return;
	}

	$fee = ITE_Fee_Line_Item::create(
		$request->get_prorate_type() === 'upgrade' ? __( 'Upgrade Trial', 'LION' ) : __( 'Downgrade Trial', 'LION' ),
		$item->get_total() * -1,
		true,
		false
	);

	$fee->set_param( 'is_prorate_days', true );

	$item->add_item( $fee );
	$cart->get_repository()->save( $item );
}

/**
 * Add fees for prorate requests that use the 'credit' type.
 *
 * @since 2.0.0
 *
 * @param ITE_Cart_Product $item
 * @param ITE_Cart         $cart
 */
function it_exchange_recurring_payments_add_credit_fees( ITE_Cart_Product $item, ITE_Cart $cart ) {

	$product_id = $item->get_product()->ID;
	$request    = ITE_Prorate_Credit_Request::get( $item->get_product(), $cart );
	$credit     = 0;

	$existing = $item->get_line_items()->with_only( 'fee' )->filter( function( ITE_Fee_Line_Item $fee ) {
		return $fee->has_param( 'is_prorate_credit' );
	} );

	if ( $existing->count() > 0 ) {
		return;
	}

	if ( $request && $request->get_credit_type() === 'credit' ) {
		$credit = min( $request->get_credit(), $item->get_total() );
	} elseif ( $cart->get_customer() && ! $cart->get_customer() instanceof IT_Exchange_Guest_Customer ) {

		$recurring_profile = it_exchange_get_recurring_product_profile( $product_id );

		$query = new ITE_Transaction_Query( array(
			'items'      => array( 'product' => $product_id ),
			'customer'   => $cart->get_customer()->ID,
			'order_date' => array(
				// Credit can only be used once the first period of the subscription has passed.
				// Give them a day lee-way to use the credit before hand if they don't want service interruption
				'before' => date( 'Y-m-d H:i:s', time() - $recurring_profile->get_interval_seconds() + DAY_IN_SECONDS )
			),
			'order'      => array(
				'order_date' => 'DESC'
			)
		) );

		foreach ( $query->results() as $transaction ) {
			if ( $transaction->cart()->has_meta( 'prorate_credit_remaining_' . $product_id ) ) {
				$credit = $transaction->cart()->get_meta( 'prorate_credit_remaining_' . $product_id );
				break;
			}
		}
	}

	if ( ! $credit ) {
		return;
	}

	$fee = ITE_Fee_Line_Item::create(
		$request->get_prorate_type() === 'upgrade' ? __( 'Upgrade Credit', 'LION' ) : __( 'Downgrade Credit', 'LION' ),
		$credit * - 1,
		true,
		false
	);
	$fee->set_param( 'is_prorate_credit', true );
	$item->add_item( $fee );
	$cart->get_repository()->save( $item );

	if ( $credit < $request->get_credit() ) {
		$cart->set_meta( 'prorate_credit_remaining_' . $product_id, $request->get_credit() - $credit );
	}
}

/**
 * For hierarchical subscriptions.
 * Prints or returns an HTML formatted list of subscriptions and their children
 *
 * @since 2.0.0
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

/**
 * Helper function to determine if customers are allowed to pause subscriptions.
 *
 * @since 2.0.0
 *
 * @return bool
 */
function it_exchange_allow_customers_to_pause_subscriptions() {

	$settings = it_exchange_get_option( 'addon_recurring_payments', true );

	return ! empty( $settings['pause-subscription'] );
}

/**
 * Helper function to determine if customers are allowed to pause subscriptions.
 *
 * @since 2.0.0
 *
 * @return int|false
 */
function it_exchange_customer_pause_subscription_limit() {

	$settings = it_exchange_get_option( 'addon_recurring_payments', true );

	if ( empty( $settings['limit-pauses'] ) ) {
		return false;
	}

	return (int) $settings['limit-pauses'];
}