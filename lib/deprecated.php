<?php
/**
 * Contains deprecated stuff.
 *
 * @since   1.8
 * @license GPLv2
 */

/**
 * Fire deprecated hooks.
 *
 * @since 1.8
 *
 * @param string                   $new_status
 * @param string                   $old_status
 * @param IT_Exchange_Subscription $subscription
 */
function it_exchange_recurring_payments_deprecated_status_hooks( $new_status, $old_status, IT_Exchange_Subscription $subscription ) {

	$transaction = $subscription->get_transaction();

	do_action( 'it_exchange_recurring_payments_addon_update_transaction_subscriber_status', $transaction, $new_status, $old_status );
	do_action( 'it_exchange_recurring_payments_addon_update_transaction_subscriber_status_' . $transaction->transaction_method, $transaction, $new_status, $old_status );
}

add_action( 'it_exchange_transition_subscription_status', 'it_exchange_recurring_payments_deprecated_status_hooks', 10, 3 );

/**
 * Updates a transaction with a new subscriber_status
 *
 * @since      1.0.0
 *
 * @deprecated 1.8
 *
 * @param mixed  $transaction       iThemes Exchange Transaction Object or ID
 * @param string $subscriber_id     Payment Gateway Subscription ID
 * @param string $subscriber_status Payment Gateway Subscription status
 *
 * @return string $subscriber_status
 */
function it_exchange_recurring_payments_addon_update_transaction_subscription_status( $transaction, $subscriber_id, $subscriber_status ) {

	_deprecated_function( __FUNCTION__, '1.8', 'IT_Exchange_Subscription::set_status' );

	$transaction = it_exchange_get_transaction( $transaction );

	if ( ! $transaction->ID ) {
		return false;
	}

	$subscription = it_exchange_get_subscription_by_transaction( $transaction );

	if ( $subscription->get_status() === $subscriber_status ) {
		return false;
	}

	$subscription->set_status( $subscriber_status );

	return $subscriber_status;

}

/**
 * Fires deprecated filters when expirations are updated.
 *
 * @since 1.8
 *
 * @param int                      $time
 * @param IT_Exchange_Subscription $subscription
 *
 * @return int
 */
function it_exchange_recurring_payments_deprecated_expirations_filters( $time, IT_Exchange_Subscription $subscription ) {

	$interval = $subscription->get_recurring_profile()->get_interval_type();
	$count    = $subscription->get_recurring_profile()->get_interval_count();
	$auto     = $subscription->is_auto_renewing();
	$method   = $subscription->get_transaction()->transaction_method;

	if ( has_filter( 'it_exchange_recurring_payments_addon_expires_time' ) ) {
		_deprecated_function( 'Filter: it_exchange_recurring_payments_addon_expires_time', '1.8',
			'it_exchange_bump_subscription_new_expiration_date' );

		$time = apply_filters( 'it_exchange_recurring_payments_addon_expires_time', $time, $interval, $count, $auto );
	}

	if ( has_filter( 'it_exchange_recurring_payments_addon_expires_time_' . $method ) ) {
		_deprecated_function( 'Filter: it_exchange_recurring_payments_addon_expires_time_{$method}', '1.8',
			'it_exchange_bump_subscription_new_expiration_date' );

		$time = apply_filters( 'it_exchange_recurring_payments_addon_expires_time_' . $method, $time, $interval, $count, $auto );
	}

	return $time;
}

add_filter( 'it_exchange_bump_subscription_new_expiration_date', 'it_exchange_recurring_payments_deprecated_expirations_filters', 10, 2 );

/**
 * Fire deprecated hooks when the subscriber ID is updated.
 *
 * @since 1.8.4
 *
 * @param IT_Exchange_Subscription $subscription
 */
function it_exchange_recurring_payments_deprecated_subscriber_id_actions( IT_Exchange_Subscription $subscription ) {

	$txn_id = $subscription->get_transaction();
	$sub_id = $subscription->get_subscriber_id();

	do_action( 'it_exchange_recurring_payments_addon_update_transaction_subscriber_id', $txn_id, $sub_id );
	do_action( 'it_exchange_recurring_payments_addon_update_transaction_subscriber_id_' . $txn_id->transaction_method, $txn_id, $sub_id );
}

add_action( 'it_exchange_subscription_set_subscriber_id', 'it_exchange_recurring_payments_deprecated_subscriber_id_actions' );

/**
 * Build the interval string.
 *
 * @deprecated 1.8
 *
 * @param $interval
 * @param $count
 *
 * @return string
 */
function it_exchange_recurring_payments_addon_interval_string( $interval, $count ) {

	_deprecated_function( __FUNCTION__, '1.8' );

	if ( 1 < $count ) {
		return $interval . 's';
	} else {
		return $interval;
	}

}