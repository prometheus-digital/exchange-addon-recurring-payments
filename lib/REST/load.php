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

	$subscription = new Subscription( new SubscriptionSerializer(), new ITE_Gateway_Request_Factory() );
	$manager->register_route( $subscription );
	$cancel = new Cancel( new SubscriptionSerializer() );
	$manager->register_route( $cancel->set_parent( $subscription ) );

	$prorate_serializer = new ProrateSerializer();
	$requestor = new ITE_Prorate_Credit_Requestor( new ITE_Daily_Price_Calculator() );
	$requestor->register_provider( 'IT_Exchange_Subscription' );
	$requestor->register_provider( 'IT_Exchange_Transaction' );

	$upgrades   = new Upgrades( $prorate_serializer, $requestor );
	$downgrades = new Downgrades( $prorate_serializer, $requestor );

	$manager->register_route( $upgrades->set_parent( $subscription ) );
	$manager->register_route( $downgrades->set_parent( $subscription ) );
} );