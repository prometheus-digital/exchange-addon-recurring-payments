<?php
/**
 * Load the REST module.
 *
 * @since   1.9.0
 * @license GPLv2
 */

use iThemes\Exchange\RecurringPayments\REST\Subscriptions\Cancel;
use iThemes\Exchange\RecurringPayments\REST\Subscriptions\Serializer as SubscriptionSerializer;
use iThemes\Exchange\RecurringPayments\REST\Subscriptions\Subscription;

add_action( 'it_exchange_register_rest_routes', function ( \iThemes\Exchange\REST\Manager $manager ) {

	$subscription = new Subscription( new SubscriptionSerializer() );
	$manager->register_route( $subscription );
	$cancel = new Cancel( new SubscriptionSerializer() );
	$manager->register_route( $cancel->set_parent( $subscription ) );
} );