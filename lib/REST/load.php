<?php
/**
 * Load the REST module.
 *
 * @since   1.9.0
 * @license GPLv2
 */

use iThemes\Exchange\RecurringPayments\REST\Subscriptions\Cancel;
use iThemes\Exchange\RecurringPayments\REST\Subscriptions\Downgrades;
use iThemes\Exchange\RecurringPayments\REST\Subscriptions\ProrateSerializer;
use iThemes\Exchange\RecurringPayments\REST\Subscriptions\Serializer as SubscriptionSerializer;
use iThemes\Exchange\RecurringPayments\REST\Subscriptions\Subscription;
use iThemes\Exchange\RecurringPayments\REST\Subscriptions\Upgrades;

add_action( 'it_exchange_register_rest_routes', function ( \iThemes\Exchange\REST\Manager $manager ) {

	$subscription = new Subscription( new SubscriptionSerializer() );
	$manager->register_route( $subscription );
	$cancel = new Cancel( new SubscriptionSerializer() );
	$manager->register_route( $cancel->set_parent( $subscription ) );

	$prorate_serializer = new ProrateSerializer();
	$requestor          = new ITE_Prorate_Credit_Requestor( new ITE_Daily_Price_Calculator() );

	$upgrades   = new Upgrades( $prorate_serializer, $requestor );
	$downgrades = new Downgrades( $prorate_serializer, $requestor );

	$manager->register_route( $upgrades->set_parent( $subscription ) );
	$manager->register_route( $downgrades->set_parent( $subscription ) );
} );

/**
 * Get a subscription from a REST id.
 *
 * @since 1.9.0
 *
 * @param string $id
 *
 * @return IT_Exchange_Subscription|WP_Error
 */
function it_exchange_get_subscription_from_rest_id( $id ) {
	$parts = explode( ':', $id );

	if ( count( $parts ) !== 2 ) {
		return new \WP_Error(
			'it_exchange_rest_invalid_subscription',
			__( 'Invalid subscription id.', 'LION' ),
			array( 'status' => \WP_Http::BAD_REQUEST )
		);
	}

	$transaction = it_exchange_get_transaction( $parts[0] );
	$product     = it_exchange_get_product( $parts[1] );

	if ( ! $transaction || ! $product ) {
		return new \WP_Error(
			'it_exchange_rest_invalid_subscription',
			__( 'Invalid subscription id.', 'LION' ),
			array( 'status' => \WP_Http::BAD_REQUEST )
		);
	}

	try {
		$subscription = \IT_Exchange_Subscription::from_transaction( $transaction, $product );
	} catch ( \InvalidArgumentException $e ) {

	}

	if ( ! isset( $subscription ) ) {
		return new \WP_Error(
			'it_exchange_rest_invalid_subscription',
			__( 'Invalid subscription id.', 'LION' ),
			array( 'status' => \WP_Http::BAD_REQUEST )
		);
	}

	return $subscription;
}