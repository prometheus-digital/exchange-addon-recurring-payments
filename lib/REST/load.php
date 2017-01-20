<?php
/**
 * Load the REST module.
 *
 * @since   2.0.0
 * @license GPLv2
 */

use iThemes\Exchange\RecurringPayments\REST\Subscriptions\Cancel;
use iThemes\Exchange\RecurringPayments\REST\Subscriptions\Downgrades;
use iThemes\Exchange\RecurringPayments\REST\Subscriptions\Pause;
use iThemes\Exchange\RecurringPayments\REST\Subscriptions\ProrateSerializer;
use iThemes\Exchange\RecurringPayments\REST\Subscriptions\Renew;
use iThemes\Exchange\RecurringPayments\REST\Subscriptions\Resume;
use iThemes\Exchange\RecurringPayments\REST\Subscriptions\Serializer as SubscriptionSerializer;
use iThemes\Exchange\RecurringPayments\REST\Subscriptions\Subscription;
use iThemes\Exchange\RecurringPayments\REST\Subscriptions\Upgrades;

add_action( 'it_exchange_register_rest_routes', function ( \iThemes\Exchange\REST\Manager $manager ) {

	$serializer = new SubscriptionSerializer();

	$subscription = new Subscription( $serializer, new ITE_Gateway_Request_Factory() );
	$manager->register_route( $subscription );

	$cancel = new Pause( $serializer );
	$manager->register_route( $cancel->set_parent( $subscription ) );

	$cancel = new Resume( $serializer );
	$manager->register_route( $cancel->set_parent( $subscription ) );

	$cancel = new Cancel( $serializer );
	$manager->register_route( $cancel->set_parent( $subscription ) );

	$renew = new Renew();
	$manager->register_route( $renew->set_parent( $subscription ) );

	$prorate_serializer = new ProrateSerializer();
	$requestor          = new ITE_Prorate_Credit_Requestor( new ITE_Daily_Price_Calculator() );
	$requestor->register_provider( 'IT_Exchange_Subscription' );
	$requestor->register_provider( 'IT_Exchange_Transaction' );

	$helper = new ITE_Prorate_REST_Helper(
		it_exchange_object_type_registry()->get( 'membership' ),
		$requestor,
		$manager,
		$prorate_serializer,
		'membership_id'
	);

	$upgrades = new Upgrades( $prorate_serializer, $helper );
	$manager->register_route( $upgrades->set_parent( $subscription ) );

	$downgrades = new Downgrades( $prorate_serializer, $helper );
	$manager->register_route( $downgrades->set_parent( $subscription ) );
} );