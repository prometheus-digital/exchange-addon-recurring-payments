<?php
/**
 * ExchangeWP Recurring Payments Add-on
 * Generic functions
 * @package exchange-addon-recurring-payments
 * @since   1.0.0
 */

/**
 * Sends notification to customer upon specific status changes
 *
 * @since 1.0.0
 *
 * @param object $customer    ExchangeWP Customer Object
 * @param string $status      Subscription Status
 * @param object $transaction Transaction
 */
function it_exchange_recurring_payments_customer_notification( $customer, $status, $transaction = false ) {

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
 * Updates Expirations dates upon successful payments of recurring products
 *
 * @since 1.0.0
 *
 * @param IT_Exchange_Transaction $transaction ExchangeWP Transaction Object
 *
 * @return void
 */
function it_exchange_recurring_payments_addon_update_expirations( $transaction ) {

	if ( ! empty( $transaction->post_parent ) ) {
		$transaction = it_exchange_get_transaction( $transaction->post_parent );
	}

	foreach ( it_exchange_get_transaction_subscriptions( $transaction ) as $subscription ) {
		$subscription->bump_expiration_date();
	}
}

/**
 * Generates a recurring label
 *
 * @since CHANGEME
 *
 * @param int $product_id ExchangeWP Product ID
 *
 * @return string ExchangeWP recurring label
 */
function it_exchange_recurring_payments_addon_recurring_label( $product_id ) {

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

	$rp    = new IT_Exchange_Recurring_Profile( $interval, $interval_count );

	$label = ' ' . (string) $rp;

	if ( $trial_enabled ) {
		$show_trial = true;
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
